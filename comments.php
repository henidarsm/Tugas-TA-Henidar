<?php
session_start();
include 'components/connect.php';

if (isset($_COOKIE['user_id'])) {
    $user_id = $_COOKIE['user_id'];
} else {
    $user_id = '';
    header('location:home.php');
    exit;
}

// Stack untuk menyimpan komentar yang dihapus
if (!isset($_SESSION['deleted_comments'])) {
    $_SESSION['deleted_comments'] = [];
}

// Menangani penghapusan komentar (Stack)
if (isset($_POST['delete_comment'])) {
    $delete_id = $_POST['comment_id'];
    $delete_id = filter_var($delete_id, FILTER_SANITIZE_STRING);

    $verify_comment = $conn->prepare("SELECT * FROM `comments` WHERE id = ?");
    $verify_comment->execute([$delete_id]);

    if ($verify_comment->rowCount() > 0) {
        // Ambil komentar yang akan dihapus
        $comment = $verify_comment->fetch(PDO::FETCH_ASSOC);

        // Simpan komentar yang dihapus dalam stack (session)
        $_SESSION['deleted_comments'][] = $comment;

        // Hapus komentar dari database
        $delete_comment = $conn->prepare("DELETE FROM `comments` WHERE id = ?");
        $delete_comment->execute([$delete_id]);
        $message[] = 'Comment deleted successfully!';
    } else {
        $message[] = 'Comment already deleted!';
    }
}

// Menangani undo (mengembalikan komentar yang dihapus)
if (isset($_POST['undo_delete_comment'])) {
    if (count($_SESSION['deleted_comments']) > 0) {
        // Ambil komentar terakhir yang dihapus dari stack
        $last_deleted_comment = array_pop($_SESSION['deleted_comments']);

        // Masukkan kembali komentar ke dalam database
        $restore_comment = $conn->prepare("INSERT INTO `comments` (user_id, content_id, comment, date) VALUES (?, ?, ?, ?)");
        $restore_comment->execute([
            $last_deleted_comment['user_id'],
            $last_deleted_comment['content_id'],
            $last_deleted_comment['comment'],
            $last_deleted_comment['date']
        ]);
        $message[] = 'Comment restored successfully!';
    } else {
        $message[] = 'No comments to restore!';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>User Comments</title>

   <!-- font awesome cdn link -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">

   <!-- custom css file link -->
   <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php include 'components/user_header.php'; ?>

<section class="comments">
   <h1 class="heading">Your Comments</h1>

   <div class="show-comments">
      <?php
         $select_comments = $conn->prepare("SELECT * FROM `comments` WHERE user_id = ?");
         $select_comments->execute([$user_id]);
         if ($select_comments->rowCount() > 0) {
            while ($fetch_comment = $select_comments->fetch(PDO::FETCH_ASSOC)) {
               $select_content = $conn->prepare("SELECT * FROM `content` WHERE id = ?");
               $select_content->execute([$fetch_comment['content_id']]);
               $fetch_content = $select_content->fetch(PDO::FETCH_ASSOC);
      ?>
      <div class="box" style="<?php if ($fetch_comment['user_id'] == $user_id) { echo 'order:-1;'; } ?>">
         <div class="content">
            <span><?= $fetch_comment['date']; ?></span>
            <p> - <?= $fetch_content['title']; ?> - </p>
            <a href="watch_video.php?get_id=<?= $fetch_content['id']; ?>">View Content</a>
         </div>
         <p class="text"><?= $fetch_comment['comment']; ?></p>
         
         <?php
            if ($fetch_comment['user_id'] == $user_id) { 
         ?>
         <form action="" method="post" class="flex-btn">
            <input type="hidden" name="comment_id" value="<?= $fetch_comment['id']; ?>">
            <button type="submit" name="edit_comment" class="inline-option-btn">Edit Comment</button>
            <button type="submit" name="delete_comment" class="inline-delete-btn" onclick="return confirm('Delete this comment?');">Delete Comment</button>
         </form>
         <?php } ?>
      </div>
      <?php } ?>
      <?php } else { echo '<p class="empty">No Comments added yet!</p>'; } ?>
   </div>

   <!-- Undo Delete Button -->
   <form action="" method="post">
      <button type="submit" name="undo_delete_comment" class="inline-btn">Undo Delete</button>
   </form>

</section>

<?php include 'components/footer.php'; ?>

<!-- custom js file link -->
<script src="js/script.js"></script>

</body>
</html>
