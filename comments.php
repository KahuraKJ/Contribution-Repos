<?php
include 'connect.php';

$memberId = $_GET['usercode'] ?? null;
if (!$memberId) {
    echo '<div class="text-danger">Error: No member selected.</div>';
    exit;
}
?>

<div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white">
        💬 Comments for Member #<?= htmlspecialchars($memberId) ?>
    </div>

    <div class="card-body">
        <div id="commentsList" class="mb-3">
            <div class="text-center text-muted">Loading comments...</div>
        </div>

        <form id="commentForm" class="d-flex gap-2">
            <input type="hidden" id="member_id" value="<?= htmlspecialchars($memberId) ?>">
            
        </form>
    </div>
</div>

<script>
// Fetch and display comments
function loadComments() {
    const memberId = document.getElementById('member_id').value;
    const commentsList = document.getElementById('commentsList');
    commentsList.innerHTML = '<div class="text-center text-muted">Loading comments...</div>';

    fetch(`fetch_comments.php?member_id=${memberId}`)
        .then(res => res.json())
        .then(data => {
            let html = '';
            if (data.length === 0) {
                html = '<p class="text-muted">No comments yet for this member.</p>';
            } else {
                data.forEach(comment => {
                    html += `
                        <div class="border-bottom mb-2 pb-2">
                            <strong>${comment.admin_name}</strong>
                            <small class="text-muted">(${comment.created_at})</small><br>
                            ${comment.message}
                        </div>`;
                });
            }
            commentsList.innerHTML = html;
        })
        .catch(() => {
            commentsList.innerHTML = '<div class="text-danger">Failed to load comments.</div>';
        });
}

// Handle comment form submit
document.getElementById('commentForm').addEventListener('submit', (e) => {
    e.preventDefault();

    const member_id = document.getElementById('member_id').value;
    const admin_id = document.getElementById('admin_id').value;
    const admin_name = document.getElementById('admin_name').value;
    const message = document.getElementById('commentMessage').value.trim();

    if (!message) return;

    fetch('insert_comment.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ member_id, admin_id, admin_name, message })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            document.getElementById('commentMessage').value = '';
            loadComments(); // Refresh comments list
        } else {
            alert('❌ Failed to post comment.');
        }
    })
    .catch(() => alert('⚠️ Error submitting comment.'));
});

loadComments(); // Auto-load on open
</script>
