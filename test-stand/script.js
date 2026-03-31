let currentData = null;
let currentTestName = '';
let duplicateCounter = 0;

async function loadTests() {
    const res = await fetch('api.php?action=getTests');
    const sets = await res.json();

    const container = document.getElementById('rulesList');
    container.innerHTML = '';

    Object.keys(sets).forEach(name => {
        const div = createRuleItem(name, () => loadTest(name, sets[name]));
        container.appendChild(div);
    });

    const customDiv = createRuleItem("Своё правило", loadCustomRule);
    container.appendChild(customDiv);

    const firstChild = container.firstElementChild;
    if (firstChild) {
        firstChild.classList.add('rule-active');
    }

    if (Object.keys(sets).length > 0) {
        const firstName = Object.keys(sets)[0];
        loadTest(firstName, sets[firstName]);
    }
}


function createRuleItem(name, onClick) {
    const div = document.createElement('div');
    div.className = `rule-item px-5 py-4 rounded-2xl cursor-pointer text-gray-700 font-medium`;
    div.textContent = name;
    div.onclick = () => {
        document.querySelectorAll('.rule-item').forEach(el => el.classList.remove('rule-active'));
        div.classList.add('rule-active');
        onClick();
    };
    return div;
}

function loadTest(name, data) {
    currentTestName = name;
    currentData = JSON.parse(JSON.stringify(data));
    duplicateCounter = currentData.duplicates ? currentData.duplicates.length : 0;

    renderNewLead();
    renderDuplicates();
    resetResult();
}

function loadCustomRule() {
    currentTestName = "Своё правило";
    currentData = {
        newLead: {ID: "", TITLE: "Новый лид", DATE_CREATE: "", UF_CRM_1668339568358: "", STATUS_ID: "1"},
        duplicates: []
    };
    duplicateCounter = 0;

    renderNewLead();
    renderDuplicates();
    resetResult();
}

function renderNewLead() {
    const nl = currentData.newLead || {};
    document.getElementById('newLeadCard').innerHTML = `
    <div class="lead-card bg-white border border-gray-200 rounded-2xl p-6">
      <input id="newTitle" value="${nl.TITLE || ''}" class="w-full text-sm font-semibold focus:outline-none border-b pb-2">
      <div class="mt-5 space-y-4 text-sm">
        <div class="flex justify-between"><span class="text-gray-500">ID</span> 
          <input id="newId" value="${nl.ID || ''}" class="font-mono text-right w-24">
        </div>
        <div class="flex justify-between"><span class="text-lg text-gray-500">🆕</span> 
          <input type="datetime-local" id="newDateCreate" value="${resolveDate(nl.DATE_CREATE)}">
        </div>
        <div class="flex justify-between"><span class="text-lg text-gray-500">⚕️</span> 
          <input type="datetime-local" id="newRegister" value="${resolveDate(nl.UF_CRM_1668339568358)}">
        </div>
      </div>
    </div>
  `;
}

function renderDuplicates() {
    const container = document.getElementById('duplicatesContainer');
    container.innerHTML = '';

    (currentData.duplicates || []).forEach((lead, i) => {
        const card = document.createElement('div');
        card.className = 'lead-card bg-white border border-gray-200 rounded-2xl p-6';
        card.innerHTML = `
      <input id="dupTitle${i}" value="${lead.TITLE || ''}" class="w-full text-sm font-semibold focus:outline-none border-b pb-2">
      <div class="mt-5 space-y-4 text-sm">
        <div class="flex justify-between"><span class="text-gray-500">ID</span> 
          <input id="dupId${i}" value="${lead.ID || ''}" class="font-mono text-right w-24">
        </div>
        <div class="flex justify-between"><span class="text-lg text-gray-500">🆕</span> 
          <input type="datetime-local" id="dupDate${i}" value="${resolveDate(lead.DATE_CREATE)}">
        </div>
        <div class="flex justify-between"><span class="text-lg text-gray-500">⚕️</span> 
          <input type="datetime-local" id="dupRegister${i}" value="${resolveDate(lead.UF_CRM_1668339568358)}">
        </div>
      </div>
    `;
        container.appendChild(card);
    });
}

function addNewDuplicate() {
    if (!currentData.duplicates) currentData.duplicates = [];

    currentData.duplicates.push({
        ID: "",
        TITLE: "Новый дубль",
        DATE_CREATE: "",
        UF_CRM_1668339568358: ""
    });

    renderDuplicates();
}

function resetResult() {
    document.getElementById('resultContainer').innerHTML = `
    <div class="text-center text-gray-400 py-20">
      Нажмите "Запустить обработку"<br>для получения результата
    </div>`;
    document.getElementById('mergeBlock').classList.add('hidden');
}

async function runBackendSelection() {
    if (!currentData) return;

    const newLead = {
        ID: document.getElementById('newId').value,
        TITLE: document.getElementById('newTitle').value,
        DATE_CREATE: document.getElementById('newDateCreate').value ? document.getElementById('newDateCreate').value + ':00+05:00' : '',
        UF_CRM_1668339568358: document.getElementById('newRegister').value ? document.getElementById('newRegister').value + ':00+05:00' : ''
    };

    const duplicates = (currentData.duplicates || []).map((_, i) => ({
        ID: document.getElementById(`dupId${i}`).value,
        TITLE: document.getElementById(`dupTitle${i}`).value,
        DATE_CREATE: document.getElementById(`dupDate${i}`).value ? document.getElementById(`dupDate${i}`).value + ':00+05:00' : '',
        UF_CRM_1668339568358: document.getElementById(`dupRegister${i}`).value ? document.getElementById(`dupRegister${i}`).value + ':00+05:00' : ''
    }));

    const res = await fetch('api.php?action=process', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({newLead, duplicates})
    });

    const result = await res.json();
    showResult(result);
}

function showResult(result) {
    const container = document.getElementById('resultContainer');
    const mergeBlock = document.getElementById('mergeBlock');

    if (!result.mainLead) {
        container.innerHTML = `<p class="text-red-500 text-center">Ошибка обработки</p>`;
        return;
    }

    container.innerHTML = `
    <div class="lead-card bg-white border border-gray-200 rounded-2xl p-4">
      <div class="font-semibold text-sm mb-1">${result.mainLead.TITLE || 'Главный лид'}</div>
      <div class="text-sm text-gray-600">ID: ${result.mainLead.ID || '—'}</div>
    </div>
  `;

    if (result.leadsToMerge && result.leadsToMerge.length > 0) {
        mergeBlock.classList.remove('hidden');
        document.getElementById('mergeCount').textContent = result.leadsToMerge.length;

        let html = '';
        result.leadsToMerge.forEach(l => {
            html += `<div class="bg-gray-50 border border-gray-200 rounded-2xl p-4">${l.TITLE || ''} <span class="text-xs text-gray-500">(ID: ${l.ID})</span></div>`;
        });
        document.getElementById('mergeList').innerHTML = html;
    } else {
        mergeBlock.classList.add('hidden');
    }
}

loadTests();

function resolveDate(dateStr) {
    if (!dateStr || typeof dateStr !== 'string') return '';

    const now = new Date();

    if (dateStr === "CURRENT") {
        return formatDateForInput(now);
    }

    const match = dateStr.match(/^CURRENT_(PLUS|MINUS)_(\d+)_(HOURS|DAYS)(?:_(\d+):(\d+))?$/);

    if (match) {
        const sign = match[1] === 'PLUS' ? 1 : -1;
        const amount = parseInt(match[2]);
        const unit = match[3] === 'DAYS' ? 24 * 60 * 60 * 1000 : 60 * 60 * 1000;

        const d = new Date(now.getTime() + sign * amount * unit);

        if (match[4]) {
            d.setHours(parseInt(match[4]), parseInt(match[5]), 0, 0);
        }

        return formatDateForInput(d);
    }

    return dateStr.slice(0, 16);
}

function formatDateForInput(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    return `${year}-${month}-${day}T${hours}:${minutes}`;
}
