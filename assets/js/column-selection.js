// Definição das colunas disponíveis
const availableColumns = {
    'Identificação': {
        'prd_uid': 'CNES',
        're_cnome': 'Prestador',
        'prd_pa': 'Procedimento',
        'procedimento': 'Descrição',
        'prd_mvm': 'Competência',
        'prd_cbo': 'CBO',
        'descricao': 'Profissional',
        'prd_idade': 'Idade'
    },
    'Valores': {
        'prd_qt_a': 'Quantidade Apresentada',
        'prd_qt_p': 'Quantidade Aprovada',
        'prd_vl_p': 'Valor Aprovado'
    },
    'Classificação': {
        'rub_dc': 'Rubrica',
        'financiamento': 'Financiamento',
        'relatorio': 'Relatório Quadrimestral'
    }
};

function initializeColumnSelection() {
    const container = document.getElementById('column-selection');
    
    // Limpa o container
    container.innerHTML = '';
    
    // Cria as seções de colunas
    for (const [section, columns] of Object.entries(availableColumns)) {
        const sectionDiv = document.createElement('div');
        sectionDiv.className = 'mb-3';
        
        // Adiciona título da seção
        const sectionTitle = document.createElement('h6');
        sectionTitle.className = 'mb-2';
        sectionTitle.textContent = section;
        sectionDiv.appendChild(sectionTitle);
        
        // Adiciona as colunas da seção
        for (const [columnKey, columnLabel] of Object.entries(columns)) {
            const checkboxDiv = document.createElement('div');
            checkboxDiv.className = 'form-check';
            
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.className = 'form-check-input';
            checkbox.id = `col-${columnKey}`;
            checkbox.value = columnKey;
            checkbox.dataset.label = columnLabel;
            
            const label = document.createElement('label');
            label.className = 'form-check-label';
            label.htmlFor = `col-${columnKey}`;
            label.textContent = columnLabel;
            
            checkboxDiv.appendChild(checkbox);
            checkboxDiv.appendChild(label);
            sectionDiv.appendChild(checkboxDiv);
        }
        
        container.appendChild(sectionDiv);
    }
    
    // Adiciona botões de seleção rápida
    const buttonGroup = document.createElement('div');
    buttonGroup.className = 'mt-3';
    
    const selectAllBtn = document.createElement('button');
    selectAllBtn.className = 'btn btn-sm btn-outline-primary me-2';
    selectAllBtn.textContent = 'Selecionar Todos';
    selectAllBtn.onclick = () => toggleAllColumns(true);
    
    const unselectAllBtn = document.createElement('button');
    unselectAllBtn.className = 'btn btn-sm btn-outline-secondary';
    unselectAllBtn.textContent = 'Desmarcar Todos';
    unselectAllBtn.onclick = () => toggleAllColumns(false);
    
    buttonGroup.appendChild(selectAllBtn);
    buttonGroup.appendChild(unselectAllBtn);
    container.appendChild(buttonGroup);
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
