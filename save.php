<?php
session_start();

if ($_POST['action'] === 'add') {
    $new = [
        'question' => $_POST['question'],
        'answer' => $_POST['answer']
    ];
    $_SESSION['faqs'][] = $new;

} elseif ($_POST['action'] === 'delete' && isset($_POST['index'])) {
    unset($_SESSION['faqs'][$_POST['index']]);
    $_SESSION['faqs'] = array_values($_SESSION['faqs']); // reindex
}

header("Location: admin.php");
exit;