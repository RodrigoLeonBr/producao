// Configuração dos operadores por tipo de campo
const operatorsByType = {
    text: [
        { value: '=', label: 'Igual a' },
        { value: '<>', label: 'Diferente de' },
        { value: 'LIKE', label: 'Contém' },
        { value: 'NOT LIKE', label: 'Não Contém' }
    ],
    number: [
        { value: '=', label: 'Igual a' },
        { value: '<>', label: 'Diferente de' },
        { value: '>', label: 'Maior que' },
        { value: '>=', label: 'Maior ou igual a' },
        { value: '<', label: 'Menor que' },
        { value: '<=', label: 'Menor ou igual a' }
    ],
    date: [
        { value: '=', label: 'Igual a' },
        { value: '<>', label: 'Diferente de' },
        { value: '>', label: 'Após' },
        { value: '>=', label: 'A partir de' },
        { value: '<', label: 'Antes de' },
        { value: '<=', label: 'Até' }
    ]
};

// Mapeamento de campos para seus tipos
const fieldTypes = {
    'prd_uid': 'text',
    're_cnome': 'text',
    'prd_pa': 'text',
    'procedimento.descricao': 'text',
    'prd_cbo': 'text',
    'descricao': 'text',
    'rub_dc': 'text',
    'financiamento': 'text',
    'relatorio': 'text',
    'prd_mvm': 'text',
    'prd_idade': 'number',
    'prd_qt_a': 'number',
    'prd_qt_p': 'number',
    'prd_vl_p': 'number'
};

// Mapeamento de rótulos para campos
const fieldLabels = {
    'prd_uid': 'CNES',
    're_cnome': 'Prestador',
    'prd_pa': 'Procedimento',
    'procedimento.descricao': 'Descrição',
    'prd_mvm': 'Competência',
    'prd_cbo': 'CBO',
    'descricao': 'Profissional',
    'prd_idade': 'Idade',
    'prd_qt_a': 'Quantidade Apresentada',
    'prd_qt_p': 'Quantidade Aprovada',
    'prd_vl_p': 'Valor Aprovado',
    'rub_dc': 'Rubrica',
    'financiamento': 'Financiamento',
    'relatorio': 'Relatório Quadrimestral'
};

// Categorias de campos
const fieldCategories = {
    'Identificação': ['prd_uid', 're_cnome', 'prd_pa', 'procedimento.descricao', 'prd_mvm', 'prd_cbo', 'descricao', 'prd_idade'],
    'Valores': ['prd_qt_a', 'prd_qt_p', 'prd_vl_p'],
    'Classificação': ['rub_dc', 'financiamento', 'relatorio']
};

function createFilterRow() {
    const filterDiv = document.createElement('div');
    filterDiv.className = 'filter-container mb-3';
    
    const filterContent = document.createElement('div');
    filterContent.className = 'row g-2 align-items-center';
    
    // Campo
    const fieldCol = document.createElement('div');
    fieldCol.className = 'col-md-4';
    const fieldSelect = document.createElement('select');
    fieldSelect.className = 'form-select filter-field';
    fieldSelect.innerHTML = '<option value="">Selecione o campo</option>';
    
    for (const [category, fields] of Object.entries(fieldCategories)) {
        const group = document.createElement('optgroup');
        group.label = category;
        
        fields.forEach(field => {
            const option = document.createElement('option');
            option.value = field;
            option.textContent = fieldLabels[field];
            group.appendChild(option);
        });
        
        fieldSelect.appendChild(group);
    }
    
    fieldSelect.addEventListener('change', function() {
        const fieldType = fieldTypes[this.value] || 'text';
        const operators = operatorsByType[fieldType];
        const operatorSelect = this.closest('.filter-container').querySelector('.filter-operator');
        
        operatorSelect.innerHTML = operators.map(op => 
            `<option value="${op.value}">${op.label}</option>`
        ).join('');
        
        const valueInput = this.closest('.filter-container').querySelector('.filter-value');
        if (fieldType === 'number') {
            valueInput.type = 'number';
            valueInput.step = this.value === 'prd_vl_p' ? '0.01' : '1';
        } else {
            valueInput.type = 'text';
        }
    });
    
    fieldCol.appendChild(fieldSelect);
    
    // Operador
    const operatorCol = document.createElement('div');
    operatorCol.className = 'col-md-3';
    const operatorSelect = document.createElement('select');
    operatorSelect.className = 'form-select filter-operator';
    operatorSelect.innerHTML = operatorsByType.text.map(op => 
        `<option value="${op.value}">${op.label}</option>`
    ).join('');
    operatorCol.appendChild(operatorSelect);
    
    // Valor
    const valueCol = document.createElement('div');
    valueCol.className = 'col-md-4';
    const valueInput = document.createElement('input');
    valueInput.type = 'text';
    valueInput.className = 'form-control filter-value';
    valueInput.placeholder = 'Valor';
    valueCol.appendChild(valueInput);
    
    // Botão remover
    const removeCol = document.createElement('div');
    removeCol.className = 'col-md-1';
    const removeButton = document.createElement('button');
    removeButton.type = 'button';
    removeButton.className = 'btn btn-danger btn-sm';
    removeButton.innerHTML = '<i class="fas fa-times"></i>';
    removeButton.onclick = function() {
        this.closest('.filter-container').remove();
    };
    removeCol.appendChild(removeButton);
    
    filterContent.appendChild(fieldCol);
    filterContent.appendChild(operatorCol);
    filterContent.appendChild(valueCol);
    filterContent.appendChild(removeCol);
    
    filterDiv.appendChild(filterContent);
    return filterDiv;
}

// Inicialização
document.addEventListener('DOMContentLoaded', function() {
    const addFilterBtn = document.getElementById('add-filter-btn');
    if (addFilterBtn) {
        addFilterBtn.addEventListener('click', function() {
            const container = document.getElementById('filters-container');
            const filterRow = createFilterRow();
            container.appendChild(filterRow);
        });
    }
});
