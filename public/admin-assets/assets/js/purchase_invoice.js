document.addEventListener('DOMContentLoaded', function () {
    let rowCounter = document.querySelectorAll('#details-table tbody tr').length;

    // Initialize Select2
    function initSelect2() {
        $('.select2').select2({ width: '100%' });
    }
    initSelect2();

    // Add new row
    document.querySelector('#addRow').addEventListener('click', function () {
        rowCounter++;
        const productOptions = products.map(product => `<option value="${product.id}">${product.name}</option>`).join('');
        const newRow = `
            <tr data-row-id="${rowCounter}">
                <td>
                    <select name="details[${rowCounter}][product_id]" class="form-control select2 bg-white" required>
                        <option value="">Select Product</option>
                        ${productOptions}
                    </select>
                </td>
                <td><input type="text" name="details[${rowCounter}][quantity]" class="form-control quantity"  oninput="this.value = this.value.replace(/[^0-9.]/g, '')" required></td>
                <td><input type="text" name="details[${rowCounter}][supplier_price]" class="form-control price" oninput="this.value = this.value.replace(/[^0-9.]/g, '')"  required></td>
                <td><input type="text" name="details[${rowCounter}][discount]" class="form-control discount" oninput="this.value = this.value.replace(/[^0-9.]/g, '')" ></td>
                <td><input type="text" name="details[${rowCounter}][subtotal]" class="form-control subtotal" readonly></td>
                <td><textarea name="details[${rowCounter}][notes]" class="form-control"></textarea></td>
                <td><button type="button" class="btn btn-danger removeRow"><i class="bi-trash"></i></button></td>
            </tr>`;
        document.querySelector('#details-table tbody').insertAdjacentHTML('beforeend', newRow);

        initSelect2(); // Reinitialize Select2 for new rows
    });

    // Handle input events for calculations
    document.querySelector('#details-table').addEventListener('input', function (e) {
        if (e.target.classList.contains('quantity') || e.target.classList.contains('price') || e.target.classList.contains('discount')) {
            const row = e.target.closest('tr');
            const price = parseFloat(row.querySelector('.price').value || 0);
            const quantity = parseFloat(row.querySelector('.quantity').value || 0);
            const discount = parseFloat(row.querySelector('.discount').value || 0);
            const subtotal = (price * quantity) * (1 - discount / 100);
            row.querySelector('.subtotal').value = subtotal.toFixed(2);
        }
        calculateTotal(); // Update totals
    });

    // Calculate total amounts
    function calculateTotal() {
        let total = 0;
        document.querySelectorAll('.subtotal').forEach(sub => total += parseFloat(sub.value || 0));
        document.querySelector('#total_amount').value = total.toFixed(2);

        const discount = parseFloat(document.querySelector('#invoice_discount').value || 0);
        document.querySelector('#net_amount').value = (total - discount).toFixed(2);
    }

    // Remove row
    document.querySelector('#details-table').addEventListener('click', function (e) {
        const target = e.target.closest('.removeRow');
        if (target) {
            const row = target.closest('tr');
            row.remove();
            calculateTotal(); // Update totals after row removal
        }
    });

    // Update total and net amount when discount is changed
    const discountInput = document.querySelector('#invoice_discount');
    if (discountInput) {
        discountInput.addEventListener('input', function () {
            calculateTotal(); // Recalculate totals when discount changes
        });
    }
});
