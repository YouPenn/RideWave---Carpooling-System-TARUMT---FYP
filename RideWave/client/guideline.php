<?php
include_once('../config/config.php');
include_once('remember_check.php');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in
if (!isset($_SESSION["loggedin"])) {
    header("Location: login.php");
    exit();
}

$navUserID = $_SESSION["userID"];

// Fetch user data from the database (For NAV)
$navsql = "SELECT username, userImg FROM user WHERE userID = ?";
if ($stmt = $mysqli->prepare($navsql)) {
    $stmt->bind_param("i", $navUserID);
    $stmt->execute();
    $stmt->bind_result($navUsername, $navUserImg);
    $stmt->fetch();
    $stmt->close();
} else {
    $navUsername = "Guest";
    $navUserImg = null;
}

// Use default avatar if user image is not set or empty
$navUserImgPath = $navUserImg ? $navUserImg : "../image/user_avatar.png";

// Fetch driver ID from the driver table using $navUserID
$navDriverId = null; // Default value if the user is not a driver
$driverSql = "SELECT driverID FROM driver WHERE userID = ?";
if ($stmt = $mysqli->prepare($driverSql)) {
    $stmt->bind_param("i", $navUserID);
    $stmt->execute();
    $stmt->bind_result($navDriverId);
    $stmt->fetch();
    $stmt->close();
}

// Fetch all guidelines
$sql_guidelines = "SELECT * FROM guideline WHERE status = 'Publish' ORDER BY dateCreated DESC";
$result = $mysqli->query($sql_guidelines);
$guidelines = $result->fetch_all(MYSQLI_ASSOC);

// Function to reapply metadata
function applyMetadata($text, $metadata) {
    $formattedText = $text;
    if (!empty($metadata)) {
        $metadataArray = json_decode($metadata, true);
        if (isset($metadataArray['title'])) {
            foreach ($metadataArray['title'] as $style) {
                $word = $style['word'];
                $styles = implode('; ', $style['styles']);
                $formattedText = str_replace($word, "<span style=\"$styles\">$word</span>", $formattedText);
            }
        }
        if (isset($metadataArray['content'])) {
            foreach ($metadataArray['content'] as $style) {
                $word = $style['word'];
                $styles = implode('; ', $style['styles']);
                $formattedText = str_replace($word, "<span style=\"$styles\">$word</span>", $formattedText);
            }
        }
    }
    return $formattedText;
}
?>

<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template-free">
<head>
    <?php include_once('header.php'); ?>
    <title>Guidelines</title>
    <link href="https://cdn.jsdelivr.net/npm/froala-editor@4.0.10/css/froala_editor.pkgd.min.css" rel="stylesheet">
    <style>
        .no-results {
            text-align: center;
            margin-top: 20px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <?php include_once('nav.php'); ?>

    <div class="content-wrapper">
        <div class="container-xxl flex-grow-1 container-p-y">   
            <h4 class="fw-bold py-3 mb-4">
                <span class="text-muted fw-light">User /</span> Guidelines
            </h4> 

            <div class="card mb-4">
                <div class="card-body">
                    <input type="text" id="searchInput" class="form-control mb-4" placeholder="Search guidelines...">
<div id="guidelineContainer">
    <?php if (empty($guidelines)) : ?>
        <p class="text-center">No guidelines available.</p>
    <?php else : ?>
        <?php foreach ($guidelines as $index => $guideline) : ?>
            <div class="guideline mb-4" data-index="<?php echo $index; ?>">
                <h5 class="guideline-title">
                    <?php echo applyMetadata($guideline['title'], $guideline['formatMetadata']); ?>
                </h5>
                <p class="guideline-content">
                    <?php echo applyMetadata($guideline['content'], $guideline['formatMetadata']); ?>
                </p>
                <small class="text-muted">Created on: <?php echo htmlspecialchars($guideline['dateCreated']); ?></small>
            </div>
            <?php if ($index < count($guidelines) - 1) : ?>
                <hr class="guideline-separator">
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<p id="noResultsMessage" class="no-results" style="display: none;">No guidelines found.</p>
                </div>
            </div>
        </div>    
    </div>

    <?php include_once('footer.php'); ?>   
    <script src="https://cdn.jsdelivr.net/npm/froala-editor@4.0.10/js/froala_editor.pkgd.min.js"></script>
    <script>
document.getElementById('searchInput').addEventListener('input', function () {
    const query = this.value.toLowerCase();
    const guidelines = document.querySelectorAll('.guideline');
    const separators = document.querySelectorAll('.guideline-separator');
    let hasResults = false;

    guidelines.forEach((guideline, index) => {
        const title = guideline.querySelector('.guideline-title').textContent.toLowerCase();
        const content = guideline.querySelector('.guideline-content').textContent.toLowerCase();

        if (title.includes(query) || content.includes(query)) {
            guideline.style.display = 'block';
            if (index > 0) separators[index - 1].style.display = 'block';
            hasResults = true;
        } else {
            guideline.style.display = 'none';
            if (index > 0) separators[index - 1].style.display = 'none';
        }
    });

    document.getElementById('noResultsMessage').style.display = hasResults ? 'none' : 'block';
});

    </script>
</body>
</html>
