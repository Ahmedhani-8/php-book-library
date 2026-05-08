<?php

session_start();
// Multi-dimensional array of books with required keys
$defaultBooks = [
    [
        'id' => 1,
        'title' => 'The Great Gatsby',
        'author' => 'F. Scott Fitzgerald',
        'genre' => 'Fiction',
        'year' => 1925,
        'pages' => 180,
        'image_url' => 'https://via.placeholder.com/100x150.jpg'
    ],
    [
        'id' => 2,
        'title' => 'A Brief History of Time',
        'author' => 'Stephen Hawking',
        'genre' => 'Science',
        'year' => 1988,
        'pages' => 256,
        'image_url' => 'https://via.placeholder.com/100x150.jpg'
    ],
    [
        'id' => 3,
        'title' => '1984',
        'author' => 'George Orwell',
        'genre' => 'Fiction',
        'year' => 1949,
        'pages' => 328,
        'image_url' => 'https://via.placeholder.com/100x150.jpg'
    ]
];

if (!isset($_SESSION['books'])) {
    $_SESSION['books'] = $defaultBooks;
}

$books = $_SESSION['books'];
$genres = ["Fiction", "Non-Fiction", "Science", "History", "Biography", "Technology"];

// data and errors
$submittedData = [];
$errors = [];
$editMode = false;
$editId = null;

if (isset($_GET['edit_id'])) {
    $editId = (int)$_GET['edit_id'];
    $editMode = true;
    foreach ($books as $book) {
        if ($book['id'] == $editId) {
            $submittedData = $book;
            break;
        }
    }
}

if (isset($_POST['edit_id']) && $_POST['edit_id'] !== '') {
    $editId = (int)$_POST['edit_id'];
    $editMode = true;
}

if (isset($_POST['delete_id'])) {
    $deleteId = (int)$_POST['delete_id'];
    $books = array_values(array_filter($books, function($book) use ($deleteId) {
        return $book['id'] != $deleteId;
    }));
    $_SESSION['books'] = $books;
    $_SESSION['success'] = "Book deleted successfully!";
    header("Location: index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && !isset($_POST['delete_id'])) {
    $title = trim(htmlspecialchars($_POST['title'] ?? ''));
    $author = trim(htmlspecialchars($_POST['author'] ?? ''));
    $genre = trim(htmlspecialchars($_POST['genre'] ?? ''));
    $year = trim(htmlspecialchars($_POST['year'] ?? ''));
    $pages = trim(htmlspecialchars($_POST['pages'] ?? ''));
    $image_url = trim(htmlspecialchars($_POST['image_url'] ?? ''));

    $submittedData = [
        'title' => $title,
        'author' => $author,
        'genre' => $genre,
        'year' => $year,
        'pages' => $pages,
        'image_url' => $image_url
    ];

    $errors = [];

    if (empty($title)) {
        $errors['title'] = "Title is required.";
    } elseif (strlen($title) < 3 || strlen($title) > 120) {
        $errors['title'] = "Title must be between 3 and 120 characters.";
    }

    if (empty($author)) {
        $errors['author'] = "Author is required.";
    } elseif (count(explode(' ', trim($author))) < 2) {
        $errors['author'] = "Author must contain at least two words (first and last name).";
    }

    if (empty($genre)) {
        $errors['genre'] = "Genre is required.";
    } elseif (!in_array($genre, $genres)) {
        $errors['genre'] = "Please select a valid genre.";
    }

    if (empty($year)) {
        $errors['year'] = "Year is required.";
    } elseif (!is_numeric($year) || strlen($year) != 4 || $year < 1000 || $year > date("Y")) {
        $errors['year'] = "Year must be a 4-digit number between 1000 and " . date("Y") . ".";
    }

    if (empty($pages)) {
        $errors['pages'] = "Pages is required.";
    } elseif (!is_numeric($pages) || $pages <= 0 || intval($pages) != $pages) {
        $errors['pages'] = "Pages must be a positive integer.";
    }

    // Image URL validation 
    if (!empty($image_url)) {
        $validExtensions = ['.jpg', '.jpeg', '.png', '.gif'];
        $hasValidExtension = false;
        foreach ($validExtensions as $ext) {
            if (strtolower(substr($image_url, -strlen($ext))) === $ext) {
                $hasValidExtension = true;
                break;
            }
        }
        if (!$hasValidExtension) {
            $errors['image_url'] = "Image URL must end with .jpg, .jpeg, .png, or .gif";
        }
    }

    // If no errors, add or update book
    if (empty($errors)) {
        if ($editMode && $editId) {
            foreach ($books as &$book) {
                if ($book['id'] == $editId) {
                    $book['title'] = $title;
                    $book['author'] = $author;
                    $book['genre'] = $genre;
                    $book['year'] = (int)$year;
                    $book['pages'] = (int)$pages;
                    $book['image_url'] = $image_url;
                    break;
                }
            }
            unset($book);
            $_SESSION['books'] = $books;
            $_SESSION['success'] = "Book updated successfully!";
        } else {
            // Add new book
            // Generate new ID: max existing ID + 1
            $maxId = 0;
            foreach ($books as $book) {
                if ($book['id'] > $maxId) {
                    $maxId = $book['id'];
                }
            }
            $newBook = [
                'id' => $maxId + 1,
                'title' => $title,
                'author' => $author,
                'genre' => $genre,
                'year' => (int)$year,
                'pages' => (int)$pages,
                'image_url' => $image_url
            ];
            $books[] = $newBook;
            $_SESSION['books'] = $books;
            $_SESSION['success'] = "Book added successfully!";
        }

        
        $submittedData = [];
        $editMode = false;
        $editId = null;
        header("Location: index.php");
        exit;
    }
}

// Handle search/filter (optional feature)
$searchTerm = '';
$displayBooks = $books;

if (isset($_GET['search'])) {
    $searchTerm = trim(htmlspecialchars($_GET['search']));
    $displayBooks = array_filter($books, function($book) use ($searchTerm) {
        return stripos($book['title'], $searchTerm) !== false || 
               stripos($book['author'], $searchTerm) !== false;
    });
    $displayBooks = array_values($displayBooks);
}

// Handle sorting (optional feature)
if (isset($_GET['sort'])) {
    $sortBy = $_GET['sort'];
    usort($displayBooks, function($a, $b) use ($sortBy) {
        switch ($sortBy) {
            case 'title':
                return strcasecmp($a['title'], $b['title']);
            case 'author':
                return strcasecmp($a['author'], $b['author']);
            case 'year':
                return $a['year'] - $b['year'];
            case 'pages':
                return $a['pages'] - $b['pages'];
            default:
                return 0;
        }
    });
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personal Book Library</title>
    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 30px;
        }
        .container-main {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .book-thumbnail {
            max-width: 60px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
        }
        .form-section {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
        }
    </style>
</head>
<body>

    <div class="container container-main">
        <h1 class="mb-4">Personal Book Library</h1>

        <!-- Success Alert -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                    echo htmlspecialchars($_SESSION['success']);
                    unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Form -->
            <div class="col-lg-4 mb-4">
                <div class="form-section">
                    <h3 class="mb-4"><?php echo $editMode ? 'Edit Book' : 'Add New Book'; ?></h3>

                    <!-- Error Alert -->
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger" role="alert">
                            Please correct the errors below.
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="index.php">
                        <?php if ($editMode && $editId): ?>
                            <input type="hidden" name="edit_id" value="<?php echo htmlspecialchars($editId); ?>">
                        <?php endif; ?>
                        <!-- Title Field -->
                        <div class="mb-3">
                            <label for="title" class="form-label">Title</label>
                            <input type="text" 
                                   class="form-control <?php echo isset($errors['title']) ? 'is-invalid' : ''; ?>" 
                                   id="title" 
                                   name="title" 
                                   value="<?php echo htmlspecialchars($submittedData['title'] ?? ''); ?>">
                            <?php if (isset($errors['title'])): ?>
                                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['title']); ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Author Field -->
                        <div class="mb-3">
                            <label for="author" class="form-label">Author</label>
                            <input type="text" 
                                   class="form-control <?php echo isset($errors['author']) ? 'is-invalid' : ''; ?>" 
                                   id="author" 
                                   name="author" 
                                   value="<?php echo htmlspecialchars($submittedData['author'] ?? ''); ?>">
                            <?php if (isset($errors['author'])): ?>
                                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['author']); ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Genre Field -->
                        <div class="mb-3">
                            <label for="genre" class="form-label">Genre</label>
                            <select class="form-select <?php echo isset($errors['genre']) ? 'is-invalid' : ''; ?>" 
                                    id="genre" 
                                    name="genre">
                                <option value="">-- Select a Genre --</option>
                                <?php foreach ($genres as $g): ?>
                                    <option value="<?php echo htmlspecialchars($g); ?>" 
                                            <?php echo (isset($submittedData['genre']) && $submittedData['genre'] === $g) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($g); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['genre'])): ?>
                                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['genre']); ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Year Field -->
                        <div class="mb-3">
                            <label for="year" class="form-label">Year Published</label>
                            <input type="number" 
                                   class="form-control <?php echo isset($errors['year']) ? 'is-invalid' : ''; ?>" 
                                   id="year" 
                                   name="year" 
                                   value="<?php echo htmlspecialchars($submittedData['year'] ?? ''); ?>">
                            <?php if (isset($errors['year'])): ?>
                                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['year']); ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Pages Field -->
                        <div class="mb-3">
                            <label for="pages" class="form-label">Number of Pages</label>
                            <input type="number" 
                                   class="form-control <?php echo isset($errors['pages']) ? 'is-invalid' : ''; ?>" 
                                   id="pages" 
                                   name="pages" 
                                   value="<?php echo htmlspecialchars($submittedData['pages'] ?? ''); ?>">
                            <?php if (isset($errors['pages'])): ?>
                                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['pages']); ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Image URL Field (Optional) -->
                        <div class="mb-3">
                            <label for="image_url" class="form-label">Cover Image URL (Optional)</label>
                            <input type="text" 
                                   class="form-control <?php echo isset($errors['image_url']) ? 'is-invalid' : ''; ?>" 
                                   id="image_url" 
                                   name="image_url" 
                                   placeholder="Must end with .jpg, .jpeg, .png, or .gif"
                                   value="<?php echo htmlspecialchars($submittedData['image_url'] ?? ''); ?>">
                            <?php if (isset($errors['image_url'])): ?>
                                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['image_url']); ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-primary w-100">
                            <?php echo $editMode ? 'Update Book' : 'Add Book'; ?>
                        </button>

                        <!-- Cancel Edit Button -->
                        <?php if ($editMode): ?>
                            <a href="index.php" class="btn btn-secondary w-100 mt-2">Cancel</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- Books Table -->
            <div class="col-lg-8">
                <div class="mb-3">
                    <form method="GET" action="index.php" class="input-group">
                        <input type="text" 
                               class="form-control" 
                               name="search" 
                               placeholder="Search by title or author..." 
                               value="<?php echo htmlspecialchars($searchTerm); ?>">
                        <button class="btn btn-outline-secondary" type="submit">Search</button>
                        <?php if (!empty($searchTerm)): ?>
                            <a href="index.php" class="btn btn-outline-secondary">Clear</a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="mb-3">
                    <span class="me-2">Sort by:</span>
                    <a href="index.php?sort=title" class="btn btn-sm btn-outline-secondary">Title</a>
                    <a href="index.php?sort=author" class="btn btn-sm btn-outline-secondary">Author</a>
                    <a href="index.php?sort=year" class="btn btn-sm btn-outline-secondary">Year</a>
                    <a href="index.php?sort=pages" class="btn btn-sm btn-outline-secondary">Pages</a>
                    <a href="index.php" class="btn btn-sm btn-outline-secondary">Reset</a>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-hover table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Cover</th>
                                <th>Title</th>
                                <th>Author</th>
                                <th>Genre</th>
                                <th>Year</th>
                                <th>Pages</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($displayBooks) > 0): ?>
                                <?php foreach ($displayBooks as $book): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($book['id']); ?></td>
                                        <td>
                                            <?php if (!empty($book['image_url'])): ?>
                                                <img src="<?php echo htmlspecialchars($book['image_url']); ?>" 
                                                     alt="Cover" 
                                                     class="book-thumbnail"
                                                     onerror="this.style.display='none'">
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($book['title']); ?></td>
                                        <td><?php echo htmlspecialchars($book['author']); ?></td>
                                        <td><?php echo htmlspecialchars($book['genre']); ?></td>
                                        <td><?php echo htmlspecialchars($book['year']); ?></td>
                                        <td><?php echo htmlspecialchars($book['pages']); ?></td>
                                        <td>
                                            <a href="index.php?edit_id=<?php echo htmlspecialchars($book['id']); ?>" 
                                               class="btn btn-sm btn-warning">Edit</a>
                                            <button type="button" 
                                                    class="btn btn-sm btn-danger" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#deleteModal"
                                                    onclick="setDeleteId(<?php echo htmlspecialchars($book['id']); ?>)">
                                                Delete
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        No books found. Add a new book to get started!
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>



    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Delete Book</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this book? This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form id="deleteForm" method="POST" action="index.php" style="display: inline;">
                        <input type="hidden" id="deleteIdInput" name="delete_id" value="">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function setDeleteId(bookId) {
            document.getElementById('deleteIdInput').value = bookId;
        }
    </script>
</body>
</html>
