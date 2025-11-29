   <!-- Header Navigation -->
      <header class="fixed top-0 left-0 lg:left-64 right-0 bg-blue-900 text-white h-16 flex items-center justify-between px-4 shadow-lg z-50 mb-6">
  
  <!-- Left Icons -->
  <div class="flex items-center space-x-3">
    <!-- Sidebar toggle -->
    
  </div>
<!-- Right Info -->
<div class="flex items-center space-x-2">

  <!-- Date -->
  <div class="flex items-center justify-center w-23 h-10 bg-blue-600 text-white rounded text-sm font-medium">
    11/28/2025
  </div>

  <!-- Add / Plus -->
  <button class="flex items-center justify-center w-10 h-10 bg-blue-600 hover:bg-blue-700 rounded transition">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
    </svg>
  </button>

  <!-- Calculator -->
  <button id="calculatorBtn" class="flex items-center justify-center w-10 h-10 bg-blue-600 hover:bg-blue-700 rounded transition" title="Calculator">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 6h8M8 12h8M8 18h8M6 6v12h12V6H6z"/>
    </svg>
  </button>

  <!-- POS -->
  <button class="flex items-center justify-center w-10 h-10 bg-blue-600 hover:bg-blue-700 rounded transition font-semibold text-white">
    POS
  </button>

  <!-- Notification -->
  <button class="flex items-center justify-center w-10 h-10 bg-blue-600 hover:bg-blue-700 rounded transition">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14V11a6 6 0 00-12 0v3c0 .538-.214 1.055-.595 1.595L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
    </svg>
  </button>

  <!-- User -->
  <button class="flex items-center justify-center w-auto px-3 h-10 bg-blue-600 hover:bg-blue-700 rounded transition text-white space-x-2">
    <span>Snooker club</span>
    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A9 9 0 1118.878 6.196 9 9 0 015.12 17.804z"/>
    </svg>
  </button>

</div>
      </header>
<script>
    // Calculator functionality
document.addEventListener('DOMContentLoaded', function() {
    // Create calculator HTML
    const calculatorHTML = `
        <div id="calculatorModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
            <div class="bg-white rounded-xl shadow-lg w-80 overflow-hidden">
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 p-4">
                    <div class="flex justify-between items-center text-white">
                        <h3 class="font-bold">Calculator</h3>
                        <button id="closeCalculator" class="text-white hover:text-gray-200 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="bg-blue-800 p-3 rounded-lg mt-2">
                        <input type="text" id="calcDisplay" readonly 
                               class="w-full bg-transparent text-white text-right text-2xl font-mono outline-none" 
                               value="0">
                    </div>
                </div>
                <div class="p-4 bg-gray-50">
                    <div class="grid grid-cols-4 gap-3">
                        <button class="calc-btn bg-red-500 hover:bg-red-600 text-white p-3 rounded-lg font-bold" data-action="clear">C</button>
                        <button class="calc-btn bg-red-400 hover:bg-red-500 text-white p-3 rounded-lg font-bold" data-action="clearEntry">CE</button>
                        <button class="calc-btn bg-gray-300 hover:bg-gray-400 p-3 rounded-lg font-bold" data-action="backspace">⌫</button>
                        <button class="calc-btn bg-orange-500 hover:bg-orange-600 text-white p-3 rounded-lg font-bold" data-action="/">÷</button>
                        
                        <button class="calc-btn bg-gray-200 hover:bg-gray-300 p-3 rounded-lg font-medium" data-number="7">7</button>
                        <button class="calc-btn bg-gray-200 hover:bg-gray-300 p-3 rounded-lg font-medium" data-number="8">8</button>
                        <button class="calc-btn bg-gray-200 hover:bg-gray-300 p-3 rounded-lg font-medium" data-number="9">9</button>
                        <button class="calc-btn bg-orange-500 hover:bg-orange-600 text-white p-3 rounded-lg font-bold" data-action="*">×</button>
                        
                        <button class="calc-btn bg-gray-200 hover:bg-gray-300 p-3 rounded-lg font-medium" data-number="4">4</button>
                        <button class="calc-btn bg-gray-200 hover:bg-gray-300 p-3 rounded-lg font-medium" data-number="5">5</button>
                        <button class="calc-btn bg-gray-200 hover:bg-gray-300 p-3 rounded-lg font-medium" data-number="6">6</button>
                        <button class="calc-btn bg-orange-500 hover:bg-orange-600 text-white p-3 rounded-lg font-bold" data-action="-">−</button>
                        
                        <button class="calc-btn bg-gray-200 hover:bg-gray-300 p-3 rounded-lg font-medium" data-number="1">1</button>
                        <button class="calc-btn bg-gray-200 hover:bg-gray-300 p-3 rounded-lg font-medium" data-number="2">2</button>
                        <button class="calc-btn bg-gray-200 hover:bg-gray-300 p-3 rounded-lg font-medium" data-number="3">3</button>
                        <button class="calc-btn bg-orange-500 hover:bg-orange-600 text-white p-3 rounded-lg font-bold" data-action="+">+</button>
                        
                        <button class="calc-btn bg-gray-200 hover:bg-gray-300 p-3 rounded-lg font-medium col-span-2" data-number="0">0</button>
                        <button class="calc-btn bg-gray-200 hover:bg-gray-300 p-3 rounded-lg font-medium" data-action=".">.</button>
                        <button class="calc-btn bg-green-500 hover:bg-green-600 text-white p-3 rounded-lg font-bold" data-action="=">=</button>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Add calculator to page
    document.body.insertAdjacentHTML('beforeend', calculatorHTML);

    // Calculator state
    let currentValue = '0';
    let previousValue = '';
    let operator = null;
    let shouldResetScreen = false;

    // Get elements
    const calculatorBtn = document.getElementById('calculatorBtn');
    const calculatorModal = document.getElementById('calculatorModal');
    const closeCalculator = document.getElementById('closeCalculator');
    const calcDisplay = document.getElementById('calcDisplay');
    const calcButtons = document.querySelectorAll('.calc-btn');

    // Calculator functions
    function updateDisplay() {
        calcDisplay.value = currentValue;
    }

    function resetCalculator() {
        currentValue = '0';
        previousValue = '';
        operator = null;
        shouldResetScreen = false;
    }

    function clearEntry() {
        currentValue = '0';
    }

    function deleteLast() {
        if (currentValue.length === 1) {
            currentValue = '0';
        } else {
            currentValue = currentValue.slice(0, -1);
        }
    }

    function appendNumber(number) {
        if (currentValue === '0' || shouldResetScreen) {
            currentValue = number;
            shouldResetScreen = false;
        } else {
            currentValue += number;
        }
    }

    function addDecimal() {
        if (shouldResetScreen) {
            currentValue = '0.';
            shouldResetScreen = false;
            return;
        }
        
        if (!currentValue.includes('.')) {
            currentValue += '.';
        }
    }

    function setOperation(newOperator) {
        if (operator !== null) {
            calculate();
        }
        previousValue = currentValue;
        operator = newOperator;
        shouldResetScreen = true;
    }

    function calculate() {
        if (operator === null || shouldResetScreen) return;
        
        const prev = parseFloat(previousValue);
        const current = parseFloat(currentValue);
        
        if (isNaN(prev) || isNaN(current)) return;
        
        let result;
        switch (operator) {
            case '+':
                result = prev + current;
                break;
            case '-':
                result = prev - current;
                break;
            case '*':
                result = prev * current;
                break;
            case '/':
                result = prev / current;
                break;
            default:
                return;
        }
        
        currentValue = result.toString();
        operator = null;
        previousValue = '';
    }

    // Event listeners for calculator buttons
    calcButtons.forEach(button => {
        button.addEventListener('click', () => {
            if (button.dataset.number) {
                appendNumber(button.dataset.number);
                updateDisplay();
            } else if (button.dataset.action) {
                const action = button.dataset.action;
                
                switch (action) {
                    case 'clear':
                        resetCalculator();
                        break;
                    case 'clearEntry':
                        clearEntry();
                        break;
                    case 'backspace':
                        deleteLast();
                        break;
                    case '=':
                        calculate();
                        break;
                    case '.':
                        addDecimal();
                        break;
                    case '+':
                    case '-':
                    case '*':
                    case '/':
                        setOperation(action);
                        break;
                }
                updateDisplay();
            }
        });
    });

    // Modal controls
    calculatorBtn.addEventListener('click', () => {
        calculatorModal.classList.remove('hidden');
    });

    closeCalculator.addEventListener('click', () => {
        calculatorModal.classList.add('hidden');
        resetCalculator();
        updateDisplay();
    });

    calculatorModal.addEventListener('click', (e) => {
        if (e.target === calculatorModal) {
            calculatorModal.classList.add('hidden');
            resetCalculator();
            updateDisplay();
        }
    });

    // Keyboard support
    document.addEventListener('keydown', (e) => {
        if (calculatorModal.classList.contains('hidden')) return;

        if (e.key >= '0' && e.key <= '9') {
            appendNumber(e.key);
            updateDisplay();
        } else if (e.key === '.') {
            addDecimal();
            updateDisplay();
        } else if (e.key === '+' || e.key === '-' || e.key === '*' || e.key === '/') {
            setOperation(e.key);
        } else if (e.key === 'Enter' || e.key === '=') {
            calculate();
            updateDisplay();
        } else if (e.key === 'Escape') {
            resetCalculator();
            updateDisplay();
        } else if (e.key === 'Backspace') {
            deleteLast();
            updateDisplay();
        }
    });

    // Initialize
    updateDisplay();
});
</script>