<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Тестовый стенд — Выбор главного лида</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .card { transition: all 0.3s ease; }
        .card:hover { transform: translateY(-6px); }
        .tab-active { border-bottom: 3px solid #10b981; color: #10b981; font-weight: 600; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen py-8">

<div class="max-w-7xl mx-auto px-6">
    <h1 class="text-4xl font-bold text-center mb-2 text-gray-800">Тестовый стенд LeadSelector</h1>
    <p class="text-center text-gray-500 mb-10">Проверка логики выбора главного лида</p>

    <div class="flex border-b mb-8 overflow-x-auto" id="tabs"></div>

    <div id="newLeadContainer" class="mb-10"></div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="duplicatesContainer"></div>

    <div class="mt-10 flex justify-center">
        <button onclick="runSelection()"
                class="px-8 py-4 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-2xl shadow-lg flex items-center gap-3 text-lg">
            <i class="fas fa-play"></i>
            Запустить выбор главного лида
        </button>
    </div>

    <div id="resultPanel" class="hidden mt-12 bg-white rounded-3xl shadow-xl p-8">
        <h2 class="text-2xl font-bold mb-6 flex items-center gap-3">
            <i class="fas fa-check-circle text-emerald-500"></i>
            Результат работы селектора
        </h2>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div>
                <p class="text-sm font-medium text-gray-500 mb-3">ГЛАВНЫЙ ЛИД</p>
                <div id="mainLeadResult" class="border-4 border-emerald-500 bg-emerald-50 rounded-2xl p-6"></div>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500 mb-3">БУДУТ ОБЪЕДИНЕНЫ</p>
                <div id="toMergeResult" class="space-y-4"></div>
            </div>
        </div>
    </div>
</div>

<script>
    // ==================== ТЕСТОВЫЕ ДАННЫЕ ====================

    const testSets = {
        "Правило №1 — IsCallTouchRule (< 24ч)": {
            newLead: { id: "1003", title: "Новый лид (триггер)", dateCreate: "2026-03-27T14:35:00+05:00", registerDate: "", status: "NEW" },
            duplicates: [
                { id: "1001", title: "Молодой старый лид (<24ч)", dateCreate: "2026-03-27T12:00:00+05:00", registerDate: "", status: "UC_VPJXTG" },
                { id: "1002", title: "Старый лид (>24ч)", dateCreate: "2026-03-20T10:00:00+05:00", registerDate: "", status: "21" }
            ]
        },

        "Правило №2 — CreatedCloseRule (запись)": {
            newLead: { id: "2003", title: "Новый лид без записи", dateCreate: "2026-03-27T14:40:00+05:00", registerDate: "", status: "NEW" },
            duplicates: [
                { id: "2001", title: "Запись уже прошла", dateCreate: "2026-01-01T00:00:00+05:00", registerDate: "2026-02-01T10:00:00+05:00", status: "UC_VPJXTG" },
                { id: "2002", title: "Запись в будущем", dateCreate: "2026-02-01T00:00:00+05:00", registerDate: "2026-04-15T10:00:00+05:00", status: "21" }
            ]
        },

        "Конфликт правил": {
            newLead: { id: "4003", title: "Новый триггерный лид", dateCreate: "2026-03-27T13:45:00+05:00", registerDate: "", status: "NEW" },
            duplicates: [
                { id: "4001", title: "Молодой + запись в будущем", dateCreate: "2026-03-27T11:00:00+05:00", registerDate: "2026-05-01T14:00:00+05:00", status: "21" },
                { id: "4002", title: "Очень старый + запись в будущем", dateCreate: "2025-12-01T00:00:00+05:00", registerDate: "2026-06-01T10:00:00+05:00", status: "21" }
            ]
        }
    };

    // ==================== РЕНДЕР ВКЛАДОК ====================

    function renderTabs() {
        const tabsContainer = document.getElementById('tabs');
        tabsContainer.innerHTML = '';

        Object.keys(testSets).forEach((name, index) => {
            const isFirst = index === 0;
            const tab = document.createElement('button');
            tab.className = `px-8 py-4 text-lg tab ${isFirst ? 'tab-active' : ''}`;
            tab.textContent = name;
            tab.onclick = () => loadTest(name);
            tabsContainer.appendChild(tab);
        });
    }

    // ==================== РЕНДЕР ТЕСТА ====================

    function loadTest(testName) {
        const data = testSets[testName];

        // Активируем вкладку
        document.querySelectorAll('.tab').forEach(t => {
            t.classList.toggle('tab-active', t.textContent === testName);
        });

        // Новый лид (жёлтый)
        document.getElementById('newLeadContainer').innerHTML = `
    <div class="bg-yellow-100 border-4 border-yellow-400 rounded-3xl p-8 shadow">
      <div class="flex items-center gap-4">
        <span class="px-5 py-2 bg-yellow-500 text-white font-semibold rounded-2xl text-sm">ВХОДЯЩИЙ ЛИД</span>
        <div>
          <h3 class="text-2xl font-semibold">${data.newLead.title}</h3>
          <p class="text-gray-600">ID: ${data.newLead.id} • Создан: ${new Date(data.newLead.dateCreate).toLocaleString('ru-RU')}</p>
        </div>
      </div>
    </div>
  `;

        // Дубликаты (красные)
        const container = document.getElementById('duplicatesContainer');
        container.innerHTML = '';

        data.duplicates.forEach(dup => {
            const hasRegister = dup.registerDate;
            const isFuture = hasRegister && new Date(dup.registerDate) > new Date();

            const html = `
      <div class="card bg-white border-2 border-red-300 hover:border-red-400 rounded-3xl p-7 shadow-sm">
        <div class="flex justify-between items-start">
          <span class="px-4 py-1.5 bg-red-100 text-red-700 text-sm font-medium rounded-2xl">ДУБЛЬ</span>
          <span class="text-xs text-gray-500">ID: ${dup.id}</span>
        </div>
        <h3 class="font-semibold text-xl mt-5 mb-3">${dup.title}</h3>
        <div class="space-y-2 text-sm text-gray-600">
          <p><strong>Создан:</strong> ${new Date(dup.dateCreate).toLocaleString('ru-RU')}</p>
          ${hasRegister ? `
            <p class="${isFuture ? 'text-emerald-600' : 'text-orange-600'}">
              <strong>Запись:</strong> ${dup.registerDate} ${isFuture ? '(в будущем)' : '(в прошлом)'}
            </p>` : '<p class="text-gray-400">Записи нет</p>'}
        </div>
      </div>
    `;
            container.innerHTML += html;
        });

        // Скрываем результат при смене теста
        document.getElementById('resultPanel').classList.add('hidden');
    }

    // ==================== ЗАПУСК СЕЛЕКТОРА ====================

    function runSelection() {
        const activeTab = document.querySelector('.tab-active').textContent;
        const data = testSets[activeTab];

        // Симуляция логики (можно потом подключить реальный PHP через fetch)
        let mainLead = data.duplicates[0]; // заглушка — берём первый дубль
        const toMerge = data.duplicates.slice(1);

        // Показываем результат
        const resultPanel = document.getElementById('resultPanel');
        resultPanel.classList.remove('hidden');

        // Главный лид
        document.getElementById('mainLeadResult').innerHTML = `
    <div class="bg-emerald-50 border-4 border-emerald-500 rounded-2xl p-6">
      <span class="px-5 py-2 bg-emerald-600 text-white text-sm font-bold rounded-2xl">ГЛАВНЫЙ</span>
      <h3 class="text-2xl font-semibold mt-4">${mainLead.title}</h3>
      <p class="text-gray-600 mt-1">ID: ${mainLead.id}</p>
    </div>
  `;

        // Список на слияние
        let mergeHTML = '';
        toMerge.forEach(lead => {
            mergeHTML += `
      <div class="bg-gray-50 border border-gray-200 rounded-2xl p-5">
        <p class="font-medium">${lead.title}</p>
        <p class="text-xs text-gray-500">ID: ${lead.id}</p>
      </div>
    `;
        });
        document.getElementById('toMergeResult').innerHTML = mergeHTML || '<p class="text-gray-400">Нет лидов для объединения</p>';
    }

    // Инициализация
    renderTabs();
    loadTest(Object.keys(testSets)[0]);
</script>

</body>
</html>