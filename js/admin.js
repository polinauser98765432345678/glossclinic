document.addEventListener('DOMContentLoaded', function() {
  // Подтверждение удаления
  const deleteButtons = document.querySelectorAll('.btn-delete');
  deleteButtons.forEach(button => {
    button.addEventListener('click', function(e) {
      if (!confirm('Вы уверены, что хотите удалить эту запись?')) {
        e.preventDefault();
      }
    });
  });

  // Анимация загрузки при отправке формы
  const forms = document.querySelectorAll('form');
  forms.forEach(form => {
    form.addEventListener('submit', function() {
      const submitButton = form.querySelector('button[type="submit"]');
      if (submitButton) {
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ' + submitButton.textContent;
        submitButton.disabled = true;
      }
    });
  });

  // Поиск в таблицах
  const searchInputs = document.querySelectorAll('.search-box input');
  searchInputs.forEach(input => {
    input.addEventListener('input', function() {
      const tableId = this.dataset.table;
      const table = document.querySelector(`#${tableId}`);
      if (!table) return;
      
      const searchTerm = this.value.toLowerCase();
      const rows = table.querySelectorAll('tbody tr');
      
      rows.forEach(row => {
        const rowText = row.textContent.toLowerCase();
        row.style.display = rowText.includes(searchTerm) ? '' : 'none';
      });
    });
  });

  // Сортировка таблиц
  const sortableHeaders = document.querySelectorAll('.admin-table th[data-sort]');
  sortableHeaders.forEach(header => {
    header.style.cursor = 'pointer';
    header.addEventListener('click', function() {
      const table = this.closest('table');
      const columnIndex = this.cellIndex;
      const isAscending = !this.classList.contains('asc');
      
      // Сброс сортировки для всех заголовков
      sortableHeaders.forEach(h => {
        h.classList.remove('asc', 'desc');
      });
      
      // Установка направления сортировки
      this.classList.toggle('asc', isAscending);
      this.classList.toggle('desc', !isAscending);
      
      // Сортировка таблицы
      sortTable(table, columnIndex, isAscending);
    });
  });

  // Анимация сообщений
  const messages = document.querySelectorAll('.message');
  messages.forEach(message => {
    setTimeout(() => {
      message.style.opacity = '0';
      setTimeout(() => message.remove(), 500);
    }, 5000);
  });

  // Функция сортировки таблицы
  function sortTable(table, columnIndex, ascending) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    rows.sort((a, b) => {
      const aText = a.cells[columnIndex].textContent.trim();
      const bText = b.cells[columnIndex].textContent.trim();
      
      // Проверка на числовые значения
      const aNum = parseFloat(aText.replace(/[^\d.-]/g, ''));
      const bNum = parseFloat(bText.replace(/[^\d.-]/g, ''));
      
      if (!isNaN(aNum) && !isNaN(bNum)) {
        return ascending ? aNum - bNum : bNum - aNum;
      }
      
      return ascending 
        ? aText.localeCompare(bText) 
        : bText.localeCompare(aText);
    });
    
    // Удаление старых строк
    rows.forEach(row => tbody.removeChild(row));
    
    // Добавление отсортированных строк
    rows.forEach(row => tbody.appendChild(row));
  }

  // Адаптивное меню для мобильных устройств
  const menuToggle = document.createElement('div');
  menuToggle.className = 'menu-toggle';
  menuToggle.innerHTML = '<i class="fas fa-bars"></i>';
  menuToggle.style.display = 'none';
  
  const adminHeader = document.querySelector('.admin-header');
  if (adminHeader) {
    adminHeader.appendChild(menuToggle);
    
    const adminSidebar = document.querySelector('.admin-sidebar');
    menuToggle.addEventListener('click', function() {
      adminSidebar.classList.toggle('active');
    });
    
    // Проверка размера экрана
    function checkScreenSize() {
      if (window.innerWidth <= 768) {
        menuToggle.style.display = 'block';
        adminSidebar.classList.remove('active');
      } else {
        menuToggle.style.display = 'none';
        adminSidebar.classList.add('active');
      }
    }
    
    window.addEventListener('resize', checkScreenSize);
    checkScreenSize();
  }
});