<?php
session_start(); 

class Book {
    public $id;
    public $title;
    public $author;
    public $available;

    public function __construct($id, $title, $author, $available = true) {
        $this->id = $id;
        $this->title = $title;
        $this->author = $author;
        $this->available = $available;
    }
}

class Member {
    public $id;
    public $name;
    public $borrowed_books = [];

    public function __construct($id, $name) {
        $this->id = $id;
        $this->name = $name;
    }

    public function borrowBook($book) {
        if ($book->available) {
            $this->borrowed_books[] = $book;
            $book->available = false;
            return true;
        } else {
            return false;
        }
    }

    public function returnBook($book) {
        foreach ($this->borrowed_books as $key => $borrowedBook) {
            if ($borrowedBook->id == $book->id) {
                unset($this->borrowed_books[$key]);
                $this->borrowed_books = array_values($this->borrowed_books); 
                $book->available = true;
                return true;
            }
        }
        return false;
    }
}


$books = [
    new Book(1, "Harry Potter", "J.K. Rowling"),
    new Book(2, "Bumi Manusia", "Pramoedya Ananta Toer"),
    new Book(3, "Laskar Pelangi", "Andrea Hirata"),
    new Book(4, "Negeri 5 Menara", "Ahmad Fuadi"),
];

$members = [];

if (isset($_SESSION['new_members'])) {
    $members = $_SESSION['new_members'];
} else {
    $members = []; 
}


$statusMessage = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['register'])) {
        if (count($members) < 10) {
            $newMemberName = $_POST['new_member_name'];
            $newMember = new Member(count($members) + 1, $newMemberName);
            $members[] = $newMember;
            $_SESSION['new_members'] = $members;
        } else {
            $statusMessage = "Jumlah anggota sudah mencapai batas maksimal (10).";
        }
    }
    if (isset($_POST['delete_member'])) {
        $memberIdToDelete = $_POST['delete_member_id'];
        foreach ($members as $key => $member) {
            if ($member->id == $memberIdToDelete) {
                unset($members[$key]);
                $members = array_values($members); 
                $_SESSION['new_members'] = $members;
                break;
            }
        }
    }

    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $memberId = isset($_POST['member_id']) ? (int)$_POST['member_id'] : 0;
    $bookId = isset($_POST['book_id']) ? (int)$_POST['book_id'] : 0;

    $member = null;
    $book = null;

    foreach ($members as $m) {
        if ($m->id == $memberId) {
            $member = $m;
            break;
        }
    }

    foreach ($books as $b) {
        if ($b->id == $bookId) {
            $book = $b;
            break;
        }
    }

    if ($member && $book) {
        if ($action === 'borrow') {
            $result = $member->borrowBook($book);
            if (!$result) {
                $statusMessage = "Buku '<strong>{$book->title}</strong>' tidak tersedia untuk dipinjam oleh <strong>{$member->name}</strong>.";
            }
        } elseif ($action === 'return') {
            $member->returnBook($book);
        }
        $_SESSION['new_members'] = $members;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Perpustakaan Online</title>
    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
            color: white;
        }
        h1, h2, p, li, label {
            font-weight: bold;
            color: white;
        }
    </style>
</head>
<body background="perpus.jpg">
    <h1>Perpustakaan Online</h1>

    <?php 
    if ($statusMessage) {
        echo "<p style='color: red;'>$statusMessage</p>";
    }
    function displayBooks($books) {
        echo "<h2>Daftar Buku</h2><ul>";
        foreach ($books as $book) {
            $status = $book->available ? "Available" : "Not Available";
            echo "<li>ID: {$book->id}, Title: {$book->title}, Author: {$book->author}, Status: {$status}</li>";
        }
        echo "</ul>";
    }

    function displayMembers($members) {
        if (empty($members)) {
            echo "<p>Tidak ada anggota terdaftar.</p>";
        } else {
            echo "<h2>Daftar Anggota</h2><ul>";
            foreach ($members as $member) {
                $borrowedTitles = array_map(function($book) { return $book->title; }, $member->borrowed_books);
                $borrowedTitlesString = implode(", ", $borrowedTitles);
                echo "<li>ID: {$member->id}, Name: {$member->name}, Borrowed Books: {$borrowedTitlesString} 
                <form method='post' style='display:inline;'>
                    <input type='hidden' name='delete_member_id' value='{$member->id}'>
                    <input type='submit' name='delete_member' value='Delete'>
                </form>
                </li>";
            }
            echo "</ul>";
        }
    }

    displayBooks($books); 
    displayMembers($members); 
    ?>

    <h2>Register Anggota Baru</h2>
    <form method="post">
        <label for="new_member_name">New Member Name:</label>
        <input type="text" id="new_member_name" name="new_member_name" required><br>
        <input type="submit" name="register" value="Register">
    </form>

    <h2>Peminjaman Atau Pengembalian Buku</h2>
    <form method="post">
        <label for="member_id">Member ID:</label>
        <input type="number" id="member_id" name="member_id" required><br>
        
        <label for="book_id">Book ID:</label>
        <input type="number" id="book_id" name="book_id" required><br>
        
        <label for="action">Action:</label>
        <select id="action" name="action">
            <option value="borrow">Borrow</option>
            <option value="return">Return</option>
        </select>
        <input type="submit" value="Submit">
    </form>
</body>
</html>