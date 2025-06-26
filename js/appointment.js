document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('appointment-form');
    
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Сброс предыдущих ошибок
        document.querySelectorAll('.error-message').forEach(el => {
            el.textContent = '';
            el.style.display = 'none';
        });
        
        // Валидация
        let isValid = true;
        const required = form.querySelectorAll('[required]');
        
        required.forEach(field => {
            if (!field.value.trim()) {
                const errorMsg = field.nextElementSibling;
                errorMsg.textContent = 'Это поле обязательно';
                errorMsg.style.display = 'block';
                isValid = false;
            }
        });
        
        if (!isValid) return;
        
        // Отправка данных
        try {
            const formData = new FormData(form);
            const response = await fetch('appointment.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Показываем сообщение об успехе
                const successDiv = document.createElement('div');
                successDiv.className = 'success-message';
                successDiv.textContent = data.message;
                form.prepend(successDiv);
                
                // Очищаем форму
                form.reset();
                
                // Через 5 секунд убираем сообщение
                setTimeout(() => {
                    successDiv.remove();
                }, 5000);
            } else {
                alert('Ошибка: ' + data.message);
            }
        } catch (error) {
            console.error('Ошибка:', error);
            alert('Произошла ошибка при отправке формы');
        }
    });
});