// Definição das colunas disponíveis
const availableColumns = [
    { key: 'prd_uid', label: 'Código', table: 's_prd' },
    { key: 're_cnome', label: 'Nome', table: 'prestador' },
    { key: 'prd_pa', label: 'Procedimento', table: 's_prd' },
    { key: 'procedimento', label: 'Desc. Procedimento', table: 'procedimento' },
    { key: 'grupo.no_grupo', label: 'Grupo', table: 'grupo' },
    { key: 'subgrupo.no_sub_grupo', label: 'Subgrupo', table: 'subgrupo' },
    { key: 'forma.no_forma', label: 'Forma', table: 'forma' },
    { key: 'prd_mvm', label: 'Competência', table: 's_prd' },
    { key: 'prd_cbo', label: 'CBO', table: 's_prd' },
    { key: 'cbo.DESCRICAO', label: 'Desc. CBO', table: 'cbo' },
    { key: 'prd_idade', label: 'Idade', table: 's_prd' },
    { key: 'prd_qt_p', label: 'Quantidade', table: 's_prd', isTotal: true },
    { key: 'prd_vl_p', label: 'Valor', table: 's_prd', isTotal: true },
    { key: 'rub_dc', label: 'Rubrica', table: 's_prd' },
    { key: 'financiamento', label: 'Financiamento', table: 's_prd' },
    { key: 'relatorio', label: 'Relatório', table: 's_prd' }
];

function initializeColumnSelection() {
    const container = document.getElementById('column-selection');
    if (!container) return;
    
    container.innerHTML = '';
    
    // Criar container para as colunas
    const columnsDiv = document.createElement('div');
    columnsDiv.className = 'row g-3';
    
    // Criar colunas em duas colunas do bootstrap
    const leftCol = document.createElement('div');
    leftCol.className = 'col-md-6';
    const rightCol = document.createElement('div');
    rightCol.className = 'col-md-6';
    
    // Dividir as colunas disponíveis em duas partes
    const midPoint = Math.ceil(availableColumns.length / 2);
    const leftColumns = availableColumns.slice(0, midPoint);
    const rightColumns = availableColumns.slice(midPoint);
    
    // Função para criar checkboxes
    function createColumnCheckboxes(columns, container) {
        columns.forEach(column => {
            const checkboxDiv = document.createElement('div');
            checkboxDiv.className = 'form-check';
            
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.className = 'form-check-input';
            checkbox.id = `col-${column.key}`;
            checkbox.value = column.key;
            checkbox.dataset.label = column.label;
            checkbox.dataset.table = column.table;
            checkbox.dataset.isTotal = column.isTotal || false;
            
            const label = document.createElement('label');
            label.className = 'form-check-label';
            label.htmlFor = `col-${column.key}`;
            label.textContent = column.label;
            
            checkboxDiv.appendChild(checkbox);
            checkboxDiv.appendChild(label);
            container.appendChild(checkboxDiv);
        });
    }
    
    // Criar checkboxes em ambas as colunas
    createColumnCheckboxes(leftColumns, leftCol);
    createColumnCheckboxes(rightColumns, rightCol);
    
    // Adicionar colunas ao container
    columnsDiv.appendChild(leftCol);
    columnsDiv.appendChild(rightCol);
    container.appendChild(columnsDiv);
}

function toggleAllColumns(select) {
    const checkboxes = document.querySelectorAll('#column-selection input[type="checkbox"]');
    checkboxes.forEach(checkbox => checkbox.checked = select);
}

function getSelectedColumns() {
    const selected = [];
    const checkboxes = document.querySelectorAll('#column-selection input[type="checkbox"]:checked');
    
    checkboxes.forEach(checkbox => {
        selected.push({
            key: checkbox.value,
            label: checkbox.dataset.label
        });
    });
    
    return selected;
}

// Inicializa a seleção de colunas quando o documento estiver pronto
document.addEventListener('DOMContentLoaded', initializeColumnSelection);
