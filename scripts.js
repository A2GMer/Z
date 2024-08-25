document.querySelectorAll('.votesButton').forEach(button => {
    button.addEventListener('click', function() {
        // XMLHttpRequestオブジェクトを作成
        var xhr = new XMLHttpRequest();
        
        var feed_id = this.getAttribute('data-id');
    
        // リクエストの設定（GETメソッド、サーバーサイドのURL）
        xhr.open('GET', `votes.php?${feed_id}`, true);
        
        // レスポンスを受け取った後の処理
        xhr.onload = function() {
            if (xhr.status === 200) {
                // 成功した場合、レスポンスを表示
                document.getElementById(`upVotesCounts?${feed_id}`).innerText = xhr.responseText;
            } else {
                // エラーが発生した場合の処理
                document.getElementById(`upVotesCounts?${feed_id}`).innerText = 'サーバ側エラー';
            }
        };
        
        // リクエストを送信
        xhr.send();
    });    
});