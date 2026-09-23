<?php
require_once '../../includes/auth.php';
include '../../includes/header.php';

// Handle Active/De-Active
if(isset($_GET['toggle']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("UPDATE categories SET is_active = NOT is_active WHERE id = ?");
    $stmt->execute([$id]);
    echo '<div class="alert alert-success">Category status updated!</div>';
}

// Delete Category
if(isset($_GET['delete']) && isset($_GET['id']) && isAdmin()) {
    $id = $_GET['id'];
    // Check if category has sub-categories
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM categories WHERE parent_id = ?");
    $stmt->execute([$id]);
    $subCount = $stmt->fetch();
    
    if($subCount['count'] > 0) {
        echo '<div class="alert alert-danger">Cannot delete category with sub-categories!</div>';
    } else {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        echo '<div class="alert alert-success">Category deleted successfully!</div>';
    }
}

$stmt = $pdo->query("SELECT * FROM categories ORDER BY parent_id, name");
$categories = $stmt->fetchAll();

// Build category tree
function buildCategoryTree($categories, $parentId = 0, $level = 0) {
    $tree = [];
    foreach($categories as $category) {
        if($category['parent_id'] == $parentId) {
            $category['level'] = $level;
            $tree[] = $category;
            $tree = array_merge($tree, buildCategoryTree($categories, $category['id'], $level + 1));
        }
    }
    return $tree;
}

$categoryTree = buildCategoryTree($categories);
?>

<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-6">
            <h2><i class="fas fa-tags"></i> Categories & Sub-Categories</h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="add.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Category
            </a>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered datatable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Category Name</th>
                            <th>Parent Category</th>
                            <th>Level</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($categoryTree as $cat): ?>
                        <tr>
                            <td><?php echo $cat['id']; ?></td>
                            <td>
                                <?php echo str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $cat['level']); ?>
                                <?php if($cat['level'] > 0): ?>
                                    <i class="fas fa-level-down-alt fa-rotate-90"></i>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </td>
                            <td>
                                <?php
                                if($cat['parent_id'] > 0) {
                                    $parent = array_filter($categories, function($c) use ($cat) {
                                        return $c['id'] == $cat['parent_id'];
                                    });
                                    $parent = array_values($parent);
                                    echo $parent[0]['name'] ?? 'N/A';
                                } else {
                                    echo '<span class="text-muted">Root Category</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php if($cat['level'] == 0): ?>
                                    <span class="badge bg-primary">Parent</span>
                                <?php elseif($cat['level'] == 1): ?>
                                    <span class="badge bg-info">Sub-Category</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Level <?php echo $cat['level']; ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $cat['is_active'] ? 'success' : 'danger'; ?>">
                                    <?php echo $cat['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td>
                                <a href="edit.php?id=<?php echo $cat['id']; ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="?toggle=1&id=<?php echo $cat['id']; ?>" class="btn btn-sm btn-warning">
                                    <i class="fas fa-<?php echo $cat['is_active'] ? 'ban' : 'check'; ?>"></i>
                                </a>
                                <?php if(isAdmin()): ?>
                                <a href="?delete=1&id=<?php echo $cat['id']; ?>" class="btn btn-sm btn-danger delete-confirm">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>