<?php
session_start();
require __DIR__ . '/db.php';

function e($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function flash(string $message): void { $_SESSION['flash'] = $message; }
function goHome(): void { header('Location: index.php'); exit; }
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }

$roles = ['Stage Actor', 'Theater Director', 'Playwright', 'Stage Manager', 'Set Designer', 'Costume Designer', 'Lighting Designer'];
$levels = ['Beginner', 'Intermediate', 'Experienced'];
$errors = [];
$form = ['id'=>'', 'first_name'=>'', 'last_name'=>'', 'email'=>'', 'phone'=>'', 'age'=>'',
         'role_applied'=>'', 'experience_level'=>'', 'skills'=>'', 'availability'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], (string)($_POST['csrf'] ?? ''))) {
        http_response_code(403);
        exit('Invalid request. Please reload the page.');
    }
    $action = (string)($_POST['action'] ?? '');
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
    if ($action === 'delete') {
        if (!$id) { $errors[] = 'Invalid record ID.'; }
        else {
            $stmt = $pdo->prepare('DELETE FROM applications WHERE id = :id');
            $stmt->execute(['id'=>$id]);
            flash($stmt->rowCount() ? 'Application deleted successfully.' : 'Record not found.');
            goHome();
        }
    } elseif (in_array($action, ['create', 'update'], true)) {
        foreach (array_keys($form) as $key) {
            if ($key !== 'id') { $form[$key] = trim((string)($_POST[$key] ?? '')); }
        }
        $form['id'] = $action === 'update' ? (string)($id ?: '') : '';
        foreach (['first_name','last_name','email','phone','age','role_applied','experience_level','skills','availability'] as $field) {
            if ($form[$field] === '') { $errors[] = str_replace('_', ' ', ucfirst($field)) . ' is required.'; }
        }
        if ($form['email'] !== '' && !filter_var($form['email'], FILTER_VALIDATE_EMAIL)) { $errors[] = 'Enter a valid email.'; }
        if ($form['age'] !== '' && (!ctype_digit($form['age']) || (int)$form['age'] < 16 || (int)$form['age'] > 100)) { $errors[] = 'Age must be 16 to 100.'; }
        if ($form['role_applied'] !== '' && !in_array($form['role_applied'], $roles, true)) { $errors[] = 'Choose a valid role.'; }
        if ($form['experience_level'] !== '' && !in_array($form['experience_level'], $levels, true)) { $errors[] = 'Choose a valid experience level.'; }
        if ($form['availability'] !== '') {
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $form['availability']);
            if (!$date || $date->format('Y-m-d') !== $form['availability']) { $errors[] = 'Enter a valid availability date.'; }
        }
        if ($action === 'update' && !$id) { $errors[] = 'Invalid record ID.'; }
        if (!$errors) {
            $data = [];
            foreach (['first_name','last_name','email','phone','age','role_applied','experience_level','skills','availability'] as $field) {
                $data[$field] = $field === 'age' ? (int)$form[$field] : $form[$field];
            }
            if ($action === 'create') {
                $sql = 'INSERT INTO applications (first_name,last_name,email,phone,age,role_applied,experience_level,skills,availability)
                        VALUES (:first_name,:last_name,:email,:phone,:age,:role_applied,:experience_level,:skills,:availability)';
            } else {
                $sql = 'UPDATE applications SET first_name=:first_name,last_name=:last_name,email=:email,phone=:phone,
                        age=:age,role_applied=:role_applied,experience_level=:experience_level,skills=:skills,availability=:availability WHERE id=:id';
                $data['id'] = $id;
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($data);
            flash($action === 'create' ? 'Application submitted successfully.' : 'Application updated successfully.');
            goHome();
        }
    } else { $errors[] = 'Unknown action.'; }
} elseif (isset($_GET['edit'])) {
    $editId = filter_var($_GET['edit'], FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
    if ($editId) {
        $stmt = $pdo->prepare('SELECT * FROM applications WHERE id=:id');
        $stmt->execute(['id'=>$editId]);
        $record = $stmt->fetch();
        if ($record) { foreach ($form as $key=>$value) { $form[$key] = (string)($record[$key] ?? ''); } }
        else { $errors[] = 'Record not found.'; }
    }
}
$editing = $form['id'] !== '';
$records = $pdo->query('SELECT * FROM applications ORDER BY id DESC')->fetchAll();
$message = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dream Stage | Theater Arts Applications</title><link rel="stylesheet" href="style.css"></head>
<body><div class="shell"><header class="hero"><p class="eyebrow">✦ THE DREAM STAGE ✦</p><h1>Theater Arts Application</h1><p>Every great performance starts with a first step. Apply for your dream role on stage or behind the scenes.</p></header>
<?php if ($message): ?><div class="notice success"><?=e($message)?></div><?php endif; ?>
<?php if ($errors): ?><div class="notice error"><strong>Please fix the following:</strong><ul><?php foreach ($errors as $error): ?><li><?=e($error)?></li><?php endforeach; ?></ul></div><?php endif; ?>
<section class="panel"><div class="section-title"><div><span class="kicker">AUDITIONS & OPPORTUNITIES</span><h2><?= $editing ? 'Edit an application' : 'New application' ?></h2></div><?php if ($editing): ?><a href="index.php" class="secondary">Cancel editing</a><?php endif; ?></div>
<form method="post" action="index.php"><input type="hidden" name="csrf" value="<?=e($_SESSION['csrf'])?>"><input type="hidden" name="action" value="<?=$editing?'update':'create'?>"><input type="hidden" name="id" value="<?=e($form['id'])?>">
<div class="fields"><label>First name<input name="first_name" maxlength="80" required value="<?=e($form['first_name'])?>" placeholder="e.g. Alex"></label>
<label>Last name<input name="last_name" maxlength="80" required value="<?=e($form['last_name'])?>" placeholder="e.g. Santos"></label>
<label>Email address<input name="email" type="email" maxlength="150" required value="<?=e($form['email'])?>" placeholder="alex@example.com"></label>
<label>Phone number<input name="phone" maxlength="30" required value="<?=e($form['phone'])?>" placeholder="09XXXXXXXXX"></label>
<label>Age<input name="age" type="number" min="16" max="100" required value="<?=e($form['age'])?>"></label>
<label>Dream role<select name="role_applied" required><option value="">Select a role</option><?php foreach($roles as $role): ?><option value="<?=e($role)?>" <?=$form['role_applied']===$role?'selected':''?>><?=e($role)?></option><?php endforeach; ?></select></label>
<label>Experience level<select name="experience_level" required><option value="">Select experience</option><?php foreach($levels as $level): ?><option value="<?=e($level)?>" <?=$form['experience_level']===$level?'selected':''?>><?=e($level)?></option><?php endforeach; ?></select></label>
<label>Available start date<input name="availability" type="date" required value="<?=e($form['availability'])?>"></label>
<label class="full">Skills and talents<textarea name="skills" rows="3" required placeholder="Acting, singing, dancing, directing, stage design…"><?=e($form['skills'])?></textarea></label></div>
<button class="primary" type="submit"><?=$editing?'Save changes':'Submit application'?> →</button></form></section>
<section class="panel"><div class="section-title"><div><span class="kicker">OUR APPLICATIONS</span><h2>Submitted applications <span class="count"><?=count($records)?></span></h2></div></div>
<div class="table-wrap"><table><thead><tr><th>ID</th><th>Applicant</th><th>Email</th><th>Phone</th><th>Age</th><th>Role</th><th>Experience</th><th>Skills</th><th>Available</th><th>Date added</th><th>Actions</th></tr></thead><tbody>
<?php if (!$records): ?><tr><td colspan="11" class="empty">No applications yet. Use the form above to submit the first one.</td></tr><?php endif; ?>
<?php foreach ($records as $r): ?><tr><td>#<?=e($r['id'])?></td><td><strong><?=e($r['first_name'].' '.$r['last_name'])?></strong></td><td><?=e($r['email'])?></td><td><?=e($r['phone'])?></td><td><?=e($r['age'])?></td><td><?=e($r['role_applied'])?></td><td><?=e($r['experience_level'])?></td><td class="skills-cell"><?=e($r['skills'])?></td><td><?=e($r['availability'])?></td><td><?=e($r['date_added'])?></td><td><div class="actions"><a class="edit" href="?edit=<?=e($r['id'])?>">Edit</a><form method="post" action="index.php" onsubmit="return confirm('Delete this application permanently?');"><input type="hidden" name="csrf" value="<?=e($_SESSION['csrf'])?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=e($r['id'])?>"><button type="submit" class="delete">Delete</button></form></div></td></tr><?php endforeach; ?>
</tbody></table></div></section><footer>Dream Stage · Theater Arts Job Application System · PHP & PDO</footer></div></body></html>
