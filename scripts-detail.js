document.querySelectorAll('.commentsButton').forEach(button => {
    button.addEventListener('click', function() {
        
        var comment_id = this.getAttribute('data-id');
    
        document.getElementById(`comment`).innerText = comment_id;
    });
});