<?php
session_start();

// Check if the user is an admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    die("Access Denied.");
}

// Database connection
$servername = "localhost";
$username = "u547027111_mvg";
$password = "MVGskola1";
$dbname = "u547027111_mvg";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Fetch images from the database
$sql = "SELECT file_path, created_at FROM rj_images ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();
$allImages = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

$categorizedImages = [];
foreach ($allImages as $image) {
    $filePath = $image['file_path'];
    $parts = explode('/', $filePath);
    $category = count($parts) > 1 ? $parts[0] : 'Kategorija';
    $categorizedImages[$category][] = $image;
}
?>

<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrācija - Attēlu Pārvaldība</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">Attēlu Pārvaldība</h1>
        <a href="../index.php" class="btn btn-secondary">⬅ Iziet uz reģistrāciju</a>
    </div>

    <?php if (isset($_GET['success']) && $_GET['success'] == 'deleted') : ?>
        <div class="alert alert-success">Attēls veiksmīgi dzēsts!</div>
    <?php endif; ?>

    <?php foreach ($categorizedImages as $category => $images): ?>
        <h3 class="mt-5"><?= htmlspecialchars($category) ?></h3>
        <div class="row">
            <?php foreach ($images as $image): ?>
                <?php
                    $fileName = basename($image['file_path']);
                    $imageUrl = "https://mvg.lv/rj/Zaudetas_mantas/{$fileName}?t=" . time();
                    $elementId = 'image-' . md5($image['file_path']);
                ?>
                <div class="col-md-3 image-card" id="<?= $elementId ?>">
                    <div class="card mb-3">
                        <img src="<?= $imageUrl ?>" class="card-img-top" alt="Image">
                        <div class="card-body text-center">
                            <p class="card-text">Ievietots: <?= date("d M Y H:i", strtotime($image['created_at'])) ?></p>
                            <button class="btn btn-danger delete-btn"
                                    data-file="<?= htmlspecialchars($image['file_path']) ?>"
                                    data-id="<?= $elementId ?>">
                                🗑 Dzēst
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
</div>

<script>
document.querySelectorAll(".delete-btn").forEach(button => {
    button.addEventListener("click", function() {
        let filePath = this.dataset.file;
        let elementId = this.dataset.id;
        let imageCard = document.getElementById(elementId);

        if (confirm("Vai tiešām vēlaties dzēst šo attēlu?")) {
            fetch("dzestbildes.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: "file_path=" + encodeURIComponent(filePath)
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === "success") {
                    imageCard.remove();
                } else {
                    alert("Kļūda: " + data.message);
                }
            })
            .catch(error => console.error("Dzēšanas kļūda:", error));
        }
    });
});
</script>

</body>
</html>
