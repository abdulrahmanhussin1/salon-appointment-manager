document.addEventListener("DOMContentLoaded", function () {
    let rowCounter = document.querySelectorAll(
        "#details-table tbody tr"
    ).length;

    // Initialize Select2
    function initSelect2() {
        $(".select2").select2({ width: "100%" });
    }
    initSelect2();

    // Add new row
    document.querySelector("#addRow").addEventListener("click", function () {
        rowCounter++;
        const productOptions = products
            .map(
                (product) =>
                    `<option value="${product.id}">${product.name}</option>`
            )
            .join("");

        const newRow = `
            <tr data-row-id="${rowCounter}">
                <td>
                    <select name="details[${rowCounter}][product_id]" class="form-control select2 bg-white" required>
                        <option value="">Select Product</option>
                        ${productOptions}
                    </select>
                </td>
                <td><input type="text" name="details[${rowCounter}][quantity]" class="form-control quantity" required></td>
                <td><input type="text" name="details[${rowCounter}][supplier_price]" class="form-control price" required></td>
                <td><input type="text" name="details[${rowCounter}][customer_price]" class="form-control customer_price" ></td>

                <td><input type="number" name="details[${rowCounter}][discount]" class="form-control discount"></td>
                <td><input type="text" name="details[${rowCounter}][subtotal]" class="form-control subtotal" readonly></td>
                <td><textarea name="details[${rowCounter}][notes]" class="form-control"></textarea></td>
                <td><button type="button" class="btn btn-danger removeRow"><i class="bi-trash"></i></button></td>
            </tr>`;
        document
            .querySelector("#details-table tbody")
            .insertAdjacentHTML("beforeend", newRow);

        initSelect2();
    });

    // Event delegation for input changes
    document.querySelector("#details-table").addEventListener(
        "input",
        debounce(function (e) {
            const target = e.target;

            // Handle calculation changes
            if (
                target.classList.contains("quantity") ||
                target.classList.contains("price") ||
                target.classList.contains("discount")
            ) {
                const row = target.closest("tr");
                updateRowSubtotal(row);
                calculateTotal();
            }

            // Prevent invalid inputs
            if (
                target.classList.contains("quantity") ||
                target.classList.contains("price")
            ) {
                validateNonNegative(target);
            }
            if (target.classList.contains("discount")) {
                validateDiscount(target);
            }
        }, 300)
    );

    // Event delegation for product duplicate check
    document
        .querySelector("#details-table")
        .addEventListener("change", function (e) {
            if (e.target.name.includes("[product_id]")) {
                if (isDuplicateProduct(e.target.value)) {
                    alert("This product is already selected!");
                    e.target.value = "";
                }
            }
        });

    // Handle row removal
    document
        .querySelector("#details-table")
        .addEventListener("click", function (e) {
            const target = e.target.closest(".removeRow");
            if (target) {
                target.closest("tr").remove();
                calculateTotal();
            }
        });

    // Update total and net amount when discount changes
    const discountInput = document.querySelector("#invoice_discount");
    if (discountInput) {
        discountInput.addEventListener("input", debounce(calculateTotal, 300));
    }

    // Form submission validation
    document
        .querySelector("#purchase-invoice-form")
        .addEventListener("submit", function (e) {
            const rows = document.querySelectorAll("#details-table tbody tr");
            if (rows.length === 0) {
                alert("Please add at least one product to the invoice!");
                e.preventDefault();
            }
        });

    // Helper functions
    function updateRowSubtotal(row) {
        const price = parseFloat(row.querySelector(".price").value || 0);
        const quantity = parseFloat(row.querySelector(".quantity").value || 0);
        const discount = parseFloat(row.querySelector(".discount").value || 0);

        const subtotal = price * quantity * (1 - discount / 100);
        row.querySelector(".subtotal").value = subtotal.toFixed(2);
    }

    function calculateTotal() {
        let total = Array.from(document.querySelectorAll(".subtotal")).reduce(
            (sum, input) => sum + parseFloat(input.value || 0),
            0
        );
        const discount = parseFloat(
            document.querySelector("#invoice_discount").value || 0
        );
        document.querySelector("#total_amount").value = total.toFixed(2);
        document.querySelector("#net_amount").value = (
            total - discount
        ).toFixed(2);
    }

    function isDuplicateProduct(productId) {
        const productIds = Array.from(
            document.querySelectorAll('select[name*="[product_id]"]')
        ).map((select) => select.value);
        return productIds.filter((id) => id === productId).length > 1;
    }

    function validateNonNegative(input) {
        const value = parseFloat(input.value || 0);
        if (value < 0) {
            alert("Value cannot be negative!");
            input.value = "";
        }
    }

    function validateDiscount(input) {
        const discount = parseFloat(input.value || 0);
        if (discount > 100) {
            alert("Discount cannot exceed 100%!");
            input.value = 100;
        }
    }

    function debounce(func, wait) {
        let timeout;
        return function (...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
});
