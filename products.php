<?php
session_start(); include 'db.php';
if (($_SESSION['role'] ?? '') !== 'Admin') { header('Location: login.php'); exit; }
mysqli_report(MYSQLI_REPORT_OFF);
$e=fn($s)=>htmlspecialchars((string)$s,ENT_QUOTES);
$dir=__DIR__.'/uploads/products/'; if(!is_dir($dir)) @mkdir($dir,0777,true);
$errors=[]; $msg=$_GET['msg']??''; $editId=(int)($_GET['edit']??0);
$old=['product_name'=>'','price'=>'','stock_quantity'=>'','category_id'=>''];

function save_photos($conn,$pid,$dir,&$errors){
  if(empty($_FILES['photos']['name'][0])) return;
  foreach($_FILES['photos']['name'] as $i=>$n){
    if($_FILES['photos']['error'][$i]!==UPLOAD_ERR_OK){ $errors[]="Could not upload $n."; continue; }
    $ext=strtolower(pathinfo($n,PATHINFO_EXTENSION)); $tmp=$_FILES['photos']['tmp_name'][$i];
    if(!in_array($ext,['jpg','jpeg','png','webp'],true)||!@getimagesize($tmp)){ $errors[]="$n must be a JPG, PNG or WEBP image."; continue; }
    if($_FILES['photos']['size'][$i]>2*1024*1024){ $errors[]="$n is larger than 2MB."; continue; }
    $f=uniqid("p{$pid}_").".$ext";
    if(move_uploaded_file($tmp,$dir.$f)){
      $st=mysqli_prepare($conn,"INSERT INTO product_photos(product_id,filename) VALUES(?,?)");
      mysqli_stmt_bind_param($st,'is',$pid,$f); mysqli_stmt_execute($st);
    }
  }
}

if($_SERVER['REQUEST_METHOD']==='POST'){
  $act=$_POST['action']??'';
  if($act==='save'){
    $id=(int)($_POST['id']??0);
    $old=['product_name'=>trim($_POST['product_name']??''),'price'=>trim($_POST['price']??''),'stock_quantity'=>trim($_POST['stock_quantity']??''),'category_id'=>trim($_POST['category_id']??'')];
    if($old['product_name']==='') $errors[]='Product name is required.';
    elseif(strlen($old['product_name'])>100) $errors[]='Product name is too long (max 100).';
    if($old['price']===''||!is_numeric($old['price'])||$old['price']<=0) $errors[]='Price must be a number greater than 0.';
    if($old['stock_quantity']===''||!ctype_digit($old['stock_quantity'])) $errors[]='Stock must be a whole number (0 or more).';
    $cid=(int)$old['category_id'];
    $c=mysqli_prepare($conn,"SELECT 1 FROM categories WHERE category_id=?"); mysqli_stmt_bind_param($c,'i',$cid); mysqli_stmt_execute($c);
    if(!mysqli_fetch_row(mysqli_stmt_get_result($c))) $errors[]='Please choose a category.';
    if(!$errors){
      $price=(float)$old['price']; $stock=(int)$old['stock_quantity'];
      if($id){ $st=mysqli_prepare($conn,"UPDATE products SET product_name=?,price=?,stock_quantity=?,category_id=? WHERE product_id=?");
        mysqli_stmt_bind_param($st,'sdiii',$old['product_name'],$price,$stock,$cid,$id); mysqli_stmt_execute($st); $pid=$id; }
      else { $st=mysqli_prepare($conn,"INSERT INTO products(product_name,price,stock_quantity,category_id) VALUES(?,?,?,?)");
        mysqli_stmt_bind_param($st,'sdii',$old['product_name'],$price,$stock,$cid); mysqli_stmt_execute($st); $pid=mysqli_insert_id($conn); }
      if(mysqli_errno($conn)) $errors[]='Database error: '.mysqli_error($conn);
      else { 
        if(!$errors){ header('Location: products.php?msg='.($id?'updated':'added')); exit; }
        $editId=$pid; }
    } else $editId=$id;
  }
  if($act==='delete'){
    $id=(int)($_POST['id']??0);
    $ph=mysqli_prepare($conn,"SELECT filename FROM product_photos WHERE product_id=?"); mysqli_stmt_bind_param($ph,'i',$id); mysqli_stmt_execute($ph);
    $files=array_column(mysqli_fetch_all(mysqli_stmt_get_result($ph),MYSQLI_ASSOC),'filename');
    $st=mysqli_prepare($conn,"DELETE FROM products WHERE product_id=?"); mysqli_stmt_bind_param($st,'i',$id);
    if(mysqli_stmt_execute($st)){ foreach($files as $f) @unlink($dir.$f); header('Location: products.php?msg=deleted'); exit; }
    $errors[]='Cannot delete this product because it is used in existing orders.';
  }
  if($act==='delphoto'){
    $pid=(int)($_POST['id']??0); $ph=(int)($_POST['photo_id']??0);
    $q=mysqli_prepare($conn,"SELECT filename FROM product_photos WHERE photo_id=? AND product_id=?"); mysqli_stmt_bind_param($q,'ii',$ph,$pid); mysqli_stmt_execute($q);
    if($r=mysqli_fetch_assoc(mysqli_stmt_get_result($q))){ @unlink($dir.$r['filename']);
      $d=mysqli_prepare($conn,"DELETE FROM product_photos WHERE photo_id=?"); mysqli_stmt_bind_param($d,'i',$ph); mysqli_stmt_execute($d); }
    header('Location: products.php?edit='.$pid); exit;
  }
}

$editing=null;
if($editId){ $q=mysqli_prepare($conn,"SELECT * FROM products WHERE product_id=?"); mysqli_stmt_bind_param($q,'i',$editId); mysqli_stmt_execute($q);
  $editing=mysqli_fetch_assoc(mysqli_stmt_get_result($q)); }
$form=$editing && !$_POST ? $editing : $old;
$cats=mysqli_fetch_all(mysqli_query($conn,"SELECT category_id,category_name FROM categories ORDER BY category_name"),MYSQLI_ASSOC);
$list=mysqli_query($conn,"SELECT p.*,c.category_name,(SELECT filename FROM product_photos WHERE product_id=p.product_id ORDER BY photo_id LIMIT 1) thumb FROM products p LEFT JOIN categories c USING(category_id) ORDER BY p.product_id");
include 'header.php';
$msgs=['added'=>'Product added.','updated'=>'Product updated.','deleted'=>'Product deleted.'];
?>
<h2>Products Management</h2>
<?php if(isset($msgs[$msg])): ?><div class="alert ok"><?= $msgs[$msg] ?></div><?php endif; ?>
<?php foreach($errors as $er): ?><div class="alert err"><?= $e($er) ?></div><?php endforeach; ?>

<div class="panel"><b><?= $editing?'Edit product':'Add new product' ?></b>
<form class="f" method="post" novalidate>
<input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= $editing?(int)$editing['product_id']:0 ?>">
<div><label>Product name</label><input type="text" name="product_name" value="<?= $e($form['product_name']) ?>"></div>
<div><label>Price (₱)</label><input type="text" name="price" value="<?= $e($form['price']) ?>"></div>
<div><label>Stock</label><input type="text" name="stock_quantity" value="<?= $e($form['stock_quantity']) ?>"></div>
<div><label>Category</label><select name="category_id"><option value="">Select category</option>
<?php foreach($cats as $c): ?><option value="<?= (int)$c['category_id'] ?>" <?= (string)$form['category_id']===(string)$c['category_id']?'selected':'' ?>><?= $e($c['category_name']) ?></option><?php endforeach; ?></select></div>
<div style="align-self:end"><button type="submit"><?= $editing?'Save changes':'Add product' ?></button>
<?php if($editing): ?> <a class="btn alt" href="products.php">Cancel</a><?php endif; ?></div>
</form>

</div>

<div class="panel"><table><tr><th>ID</th><th>Product</th><th>Price</th><th>Stock</th><th>Category</th><th>Actions</th></tr>
<?php while($r=mysqli_fetch_assoc($list)): ?><tr>
<td><?= (int)$r['product_id'] ?></td>
<td><?= $e($r['product_name']) ?></td>
<td>₱<?= number_format($r['price'],2) ?></td><td><?= (int)$r['stock_quantity'] ?></td><td><span class="pill"><?= $e($r['category_name']) ?></span></td>
<td style="white-space:nowrap"><a class="btn sm alt" href="products.php?edit=<?= (int)$r['product_id'] ?>">Edit</a>
<form method="post" style="display:inline" onsubmit="return confirm('Delete this product?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['product_id'] ?>"><button class="btn sm del">Delete</button></form></td>
</tr><?php endwhile; ?></table></div>
</main></div></body></html>