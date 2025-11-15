document.getElementById('applicationForm').addEventListener('submit', function (e) {
    e.preventDefault();

    fetch('', {
        method: 'POST',
        body: new FormData(this)
    })
        .then(r => r.json())
        .then(data => {
            const messageDiv = document.getElementById('message');
            if (data.success) {
                document.getElementById('applicationForm').style.display = 'none';
                messageDiv.style.color = 'green';
                messageDiv.innerHTML = 'Отправка успешна';
            } else {
                messageDiv.style.color = 'red';
                messageDiv.innerHTML = data.errors.join('<br>');
            }
            messageDiv.style.display = 'block';
        });
});