<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireAdminLogin();

$conn = getDBConnection();
$message = '';
$error = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $id         = intval($_POST['id'] ?? 0);
        $name       = trim($_POST['guest_name'] ?? '');
        $type       = trim($_POST['guest_type'] ?? '');
        $text       = trim($_POST['review_text'] ?? '');
        $rating     = max(1, min(5, intval($_POST['rating'] ?? 5)));
        $sort_order = intval($_POST['sort_order'] ?? 0);
        $is_active  = isset($_POST['is_active']) ? 1 : 0;

        if (empty($name) || empty($text)) {
            $error = 'Guest name and review text are required.';
        } elseif ($action === 'create') {
            $stmt = $conn->prepare("INSERT INTO guest_reviews (guest_name, guest_type, review_text, rating, sort_order, is_active) VALUES (?,?,?,?,?,?)");
            $stmt->bind_param("sssiii", $name, $type, $text, $rating, $sort_order, $is_active);
            $stmt->execute();
            $message = 'Review added successfully.';
        } else {
            $stmt = $conn->prepare("UPDATE guest_reviews SET guest_name=?, guest_type=?, review_text=?, rating=?, sort_order=?, is_active=? WHERE id=?");
            $stmt->bind_param("sssiii i", $name, $type, $text, $rating, $sort_order, $is_active, $id);
            $stmt->execute();
            $message = 'Review updated successfully.';
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        $conn->query("DELETE FROM guest_reviews WHERE id = $id");
        $message = 'Review deleted.';
    } elseif ($action === 'toggle') {
        $id = intval($_POST['id'] ?? 0);
        $conn->query("UPDATE guest_reviews SET is_active = 1 - is_active WHERE id = $id");
        $message = 'Review visibility updated.';
    }
}

// Fetch all reviews
$reviews = [];
$res = $conn->query("SELECT * FROM guest_reviews ORDER BY sort_order ASC, created_at DESC");
if ($res) while ($row = $res->fetch_assoc()) $reviews[] = $row;

// Fetch single review for edit
$editing = null;
if (isset($_GET['edit'])) {
    $eid = intval($_GET['edit']);
    $r = $conn->query("SELECT * FROM guest_reviews WHERE id = $eid");
    if ($r) $editing = $r->fetch_assoc();
}

$pageTitle  = 'Guest Reviews';
$currentPage = 'reviews';
?>
<?php include 'template_header.php'; ?>
<style>
    .reviews-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
    .review-card { background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 10px rgba(0,0,0,0.08); border-left: 4px solid #C9A961; position: relative; }
    .review-card.inactive { opacity: 0.5; border-left-color: #ccc; }
    .review-card .stars { color: #C9A961; margin: 0.4rem 0; }
    .review-card .review-text { color: #555; font-style: italic; margin-bottom: 1rem; line-height: 1.6; }
    .review-card .guest-name { font-weight: 700; color: #2C3E50; }
    .review-card .guest-type { font-size: 0.85rem; color: #888; }
    .review-card .card-actions { display: flex; gap: 0.5rem; margin-top: 1rem; }
    .btn-edit   { background: #3498db; color: white; border: none; padding: 0.4rem 0.9rem; border-radius: 6px; cursor: pointer; font-size: 0.85rem; text-decoration: none; }
    .btn-toggle { background: #f39c12; color: white; border: none; padding: 0.4rem 0.9rem; border-radius: 6px; cursor: pointer; font-size: 0.85rem; }
    .btn-delete { background: #e74c3c; color: white; border: none; padding: 0.4rem 0.9rem; border-radius: 6px; cursor: pointer; font-size: 0.85rem; }
    .form-card  { background: white; border-radius: 12px; padding: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.08); margin-bottom: 2rem; }
    .form-row   { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
    .form-group { margin-bottom: 1rem; }
    .form-group label { display: block; font-weight: 600; margin-bottom: 0.4rem; color: #2C3E50; font-size: 0.9rem; }
    .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 0.6rem 0.9rem; border: 1px solid #ddd; border-radius: 8px; font-family: inherit; font-size: 0.95rem; }
    .form-group textarea { resize: vertical; min-height: 90px; }
    .star-select { display: flex; gap: 0.5rem; align-items: center; }
    .star-select input[type=radio] { display: none; }
    .star-select label { font-size: 1.5rem; color: #ddd; cursor: pointer; }
    .star-select input[type=radio]:checked ~ label,
    .star-select label:hover,
    .star-select label:hover ~ label { color: #C9A961; }
    .star-select { flex-direction: row-reverse; justify-content: flex-end; }
    .alert-success { background: #d4edda; color: #155724; padding: 0.8rem 1.2rem; border-radius: 8px; margin-bottom: 1rem; }
    .alert-error   { background: #f8d7da; color: #721c24; padding: 0.8rem 1.2rem; border-radius: 8px; margin-bottom: 1rem; }
</style>

<div class="page-header">
    <h2><i class="fas fa-star"></i> Guest Reviews</h2>
    <p>Manage the guest reviews shown on the landing page.</p>
</div>

<?php if ($message): ?><div class="alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<!-- Add / Edit Form -->
<div class="form-card">
    <h3 style="margin-bottom:1.2rem;color:#2C3E50;"><?php echo $editing ? '<i class="fas fa-edit"></i> Edit Review' : '<i class="fas fa-plus-circle"></i> Add New Review'; ?></h3>
    <form method="POST">
        <input type="hidden" name="action" value="<?php echo $editing ? 'update' : 'create'; ?>">
        <?php if ($editing): ?><input type="hidden" name="id" value="<?php echo $editing['id']; ?>"><?php endif; ?>
        <div class="form-row">
            <div class="form-group">
                <label>Guest Name *</label>
                <input type="text" name="guest_name" value="<?php echo htmlspecialchars($editing['guest_name'] ?? ''); ?>" placeholder="e.g. John & Sarah Davis" required>
            </div>
            <div class="form-group">
                <label>Guest Type</label>
                <input type="text" name="guest_type" value="<?php echo htmlspecialchars($editing['guest_type'] ?? ''); ?>" placeholder="e.g. Honeymoon Suite Guests">
            </div>
        </div>
        <div class="form-group">
            <label>Review Text *</label>
            <textarea name="review_text" required><?php echo htmlspecialchars($editing['review_text'] ?? ''); ?></textarea>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Rating</label>
                <select name="rating">
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                    <option value="<?php echo $i; ?>" <?php echo ($editing['rating'] ?? 5) == $i ? 'selected' : ''; ?>><?php echo $i; ?> Star<?php echo $i > 1 ? 's' : ''; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Sort Order</label>
                <input type="number" name="sort_order" value="<?php echo $editing['sort_order'] ?? 0; ?>" min="0">
            </div>
        </div>
        <div class="form-group">
            <label><input type="checkbox" name="is_active" value="1" <?php echo ($editing['is_active'] ?? 1) ? 'checked' : ''; ?>> Show on landing page</label>
        </div>
        <button type="submit" class="btn-edit" style="padding:0.6rem 1.5rem;font-size:1rem;">
            <i class="fas fa-save"></i> <?php echo $editing ? 'Update Review' : 'Add Review'; ?>
        </button>
        <?php if ($editing): ?>
            <a href="reviews.php" class="btn-toggle" style="padding:0.6rem 1.2rem;font-size:1rem;text-decoration:none;">Cancel</a>
        <?php endif; ?>
    </form>
</div>

<!-- Reviews List -->
<div class="reviews-grid">
    <?php foreach ($reviews as $r): ?>
    <div class="review-card <?php echo $r['is_active'] ? '' : 'inactive'; ?>">
        <div class="stars">
            <?php for ($s = 1; $s <= 5; $s++) echo '<i class="fas fa-star' . ($s > $r['rating'] ? '-o' : '') . '"></i>'; ?>
        </div>
        <p class="review-text">"<?php echo htmlspecialchars($r['review_text']); ?>"</p>
        <div class="guest-name"><?php echo htmlspecialchars($r['guest_name']); ?></div>
        <div class="guest-type"><?php echo htmlspecialchars($r['guest_type']); ?></div>
        <div class="card-actions">
            <a href="reviews.php?edit=<?php echo $r['id']; ?>" class="btn-edit"><i class="fas fa-edit"></i> Edit</a>
            <form method="POST" style="display:inline;">
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                <button type="submit" class="btn-toggle"><i class="fas fa-eye<?php echo $r['is_active'] ? '-slash' : ''; ?>"></i> <?php echo $r['is_active'] ? 'Hide' : 'Show'; ?></button>
            </form>
            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this review?');">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                <button type="submit" class="btn-delete"><i class="fas fa-trash"></i> Delete</button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($reviews)): ?>
        <p style="color:#888;">No reviews yet. Add one above.</p>
    <?php endif; ?>
</div>

<?php include 'template_footer.php'; ?>
