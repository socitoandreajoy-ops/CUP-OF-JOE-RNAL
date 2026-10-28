<?php
session_start(); include 'db.php';
if (($_SESSION['role'] ?? '') !== 'Admin') { header('Location: login.php'); exit; }
mysqli_report(MYSQLI_REPORT_OFF);
$e=fn($s)=>htmlspecialchars((string)$s,ENT_QUOTES);
$dir=__DIR__.'/uploads/customers/'; if(!is_dir($dir)) @mkdir($dir,0777,true);
$errors=[]; $msg=$_GET['msg']??''; $editId=(int)($_GET['edit']??0); $search=trim($_GET['q']??'');
$old=['customer_name'=>'','email'=>'','phone'=>'','loyalty_points'=>'0'];

if($_SERVER['REQUEST_METHOD']==='POST'){
  $act=$_POST['action']??'';
  if($act==='save'){
    $id=(int)($_POST['id']??0);
    $old=['customer_name'=>trim($_POST['customer_name']??''),'email'=>trim($_POST['email']??''),'phone'=>trim($_POST['phone']??''),'loyalty_points'=>trim($_POST['loyalty_points']??'')];
    if($old['customer_name']==='') $errors[]='Customer name is required.';
    elseif(strlen($old['customer_name'])>100) $errors[]='Customer name is too long (max 100).';
    if($old['email']!==''&&!filter_var($old['email'],FILTER_VALIDATE_EMAIL)) $errors[]='Email format is invalid.';
    if($old['email']!==''){ $q=mysqli_prepare($conn,"SELECT 1 FROM customers WHERE email=? AND customer_id<>?"); mysqli_stmt_bind_param($q,'si',$old['email'],$id); mysqli_stmt_execute($q);
      if(mysqli_fetch_row(mysqli_stmt_get_result($q))) $errors[]='That email is already used by another customer.'; }
    if($old['phone']!==''&&!preg_match('/^(09\d{9}|\+639\d{9})$/',$old['phone'])) $errors[]='Phone must look like 09123456789 or +639123456789.';
    if($old['loyalty_points']===''||!ctype_digit($old['loyalty_points'])) $errors[]='Loyalty points must be a whole number (0 or more).';
    $photo=null;
    if(!empty($_FILES['photo']['name'])){
      $ext=strtolower(pathinfo($_FILES['photo']['name'],PATHINFO_EXTENSION));
      if($_FILES['photo']['error']!==UPLOAD_ERR_OK) $errors[]='Photo upload failed.';
      elseif(!in_array($ext,['jpg','jpeg','png','webp'],true)||!@getimagesize($_FILES['photo']['tmp_name'])) $errors[]='Photo must be a JPG, PNG or WEBP image.';
      elseif($_FILES['photo']['size']>2*1024*1024) $errors[]='Photo must be 2MB or smaller.';
      else { $photo=uniqid('c').".$ext"; }
    }
    if(!$errors){
      $em=$old['email']?:null; $ph=$old['phone']?:null; $pts=(int)$old['loyalty_points'];
      if($photo) move_uploaded_file($_FILES['photo']['tmp_name'],$dir.$photo);
      if($id){
        if($photo){ $o=mysqli_fetch_row(mysqli_query($conn,"SELECT photo FROM customers WHERE customer_id=$id"))[0]??null; if($o) @unlink($dir.$o);
          $st=mysqli_prepare($conn,"UPDATE customers SET customer_name=?,email=?,phone=?,loyalty_points=?,photo=? WHERE customer_id=?");
          mysqli_stmt_bind_param($st,'sssisi',$old['customer_name'],$em,$ph,$pts,$photo,$id); }
        else { $st=mysqli_prepare($conn,"UPDATE customers SET customer_name=?,email=?,phone=?,loyalty_points=? WHERE customer_id=?");
          mysqli_stmt_bind_param($st,'sssii',$old['customer_name'],$em,$ph,$pts,$id); }
      } else { $st=mysqli_prepare($conn,"INSERT INTO customers(customer_name,email,phone,loyalty_points,photo) VALUES(?,?,?,?,?)");
        mysqli_stmt_bind_param($st,'sssis',$old['customer_name'],$em,$ph,$pts,$photo); }
      if(mysqli_stmt_execute($st)){ header('Location: customers.php?msg='.($id?'updated':'added')); exit; }
      $errors[]='Database error: '.mysqli_error($conn);
    }
    $editId=$id;
  }
  if($act==='delete'){
    $id=(int)($_POST['id']??0);
    $o=mysqli_fetch_row(mysqli_query($conn,"SELECT photo FROM customers WHERE customer_id=$id"))[0]??null;
    mysqli_query($conn,"DELETE FROM users WHERE customer_id=$id");
    $st=mysqli_prepare($conn,"DELETE FROM customers WHERE customer_id=?"); mysqli_stmt_bind_param($st,'i',$id);
    if(mysqli_stmt_execute($st)){ if($o) @unlink($dir.$o); header('Location: customers.php?msg=deleted'); exit; }
    $errors[]='Cannot delete this customer because they have existing orders.';
  }
}

$editing=null;
if($editId){ $q=mysqli_prepare($conn,"SELECT * FROM customers WHERE customer_id=?"); mysqli_stmt_bind_param($q,'i',$editId); mysqli_stmt_execute($q);
  $editing=mysqli_fetch_assoc(mysqli_stmt_get_result($q)); }
$form=($editing && !$_POST)?$editing:$old;
$like='%'.$search.'%';
$st=mysqli_prepare($conn,"SELECT * FROM customers WHERE customer_name LIKE ? OR email LIKE ? ORDER BY customer_id DESC");
mysqli_stmt_bind_param($st,'ss',$like,$like); mysqli_stmt_execute($st); $list=mysqli_stmt_get_result($st);
include 'header.php';
$msgs=['added'=>'Customer added.','updated'=>'Customer updated.','deleted'=>'Customer deleted.'];
?>
<h2>Customers</h2>
<?php if(isset($msgs[$msg])): ?><div class="alert ok"><?= $msgs[$msg] ?></div><?php endif; ?>
<?php foreach($errors as $er): ?><div class="alert err"><?= $e($er) ?></div><?php endforeach; ?>

<div class="panel"><b><?= $editing?'Edit customer':'Add new customer' ?></b>
<form class="f" method="post" novalidate>
<input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= $editing?(int)$editing['customer_id']:0 ?>">
<div><label>Full name</label><input type="text" name="customer_name" value="<?= $e($form['customer_name']) ?>"></div>
<div><label>Email</label><input type="text" name="email" value="<?= $e($form['email']??'') ?>"></div>
<div><label>Phone</label><input type="text" name="phone" value="<?= $e($form['phone']??'') ?>" placeholder="09123456789"></div>
<div><label>Loyalty points</label><input type="text" name="loyalty_points" value="<?= $e($form['loyalty_points']) ?>"></div>
<div style="align-self:end"><button type="submit"><?= $editing?'Save changes':'Add customer' ?></button>
<?php if($editing): ?> <a class="btn alt" href="customers.php">Cancel</a><?php endif; ?></div>
</form>
</div>

<div class="panel">
<form method="get" style="display:flex;gap:10px;margin-bottom:14px"><input type="text" name="q" value="<?= $e($search) ?>" placeholder="Search name or email"><button>Search</button></form>
<table><tr><th>ID</th><th>Customer</th><th>Email</th><th>Phone</th><th>Points</th><th>Actions</th></tr>
<?php while($r=mysqli_fetch_assoc($list)): ?><tr>
<td><?= (int)$r['customer_id'] ?></td>
<td><?= $e($r['customer_name']) ?></td>
<td><?= $e($r['email']??'') ?></td><td><?= $e($r['phone']??'') ?></td><td><span class="pill"><?= (int)$r['loyalty_points'] ?> pts</span></td>
<td style="white-space:nowrap"><a class="btn sm alt" href="customers.php?edit=<?= (int)$r['customer_id'] ?>">Edit</a>
<form method="post" style="display:inline" onsubmit="return confirm('Delete this customer?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['customer_id'] ?>"><button class="btn sm del">Delete</button></form></td>
</tr><?php endwhile; ?></table></div>
</main></div></body></html>