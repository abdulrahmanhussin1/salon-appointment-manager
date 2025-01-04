// Configure API endpoints
const API_ENDPOINTS = {
    CATEGORIES: "/admin/categories",
    ITEMS: "/admin/items",
    ITEM_DETAILS: (id) => `/admin/items/${id}`,
    PROVIDERS: "/admin/get-related-employees",
    INVOICE_STORE: "/admin/sales_invoices",
};

// Enhanced notification functions using SweetAlert2
const notifications = {
    error: (message) => {
        Swal.fire({
            icon: "error",
            title: "Error",
            text: message,
            confirmButtonColor: "#dc3545",
        });
    },

    success: (message) => {
        Swal.fire({
            icon: "success",
            title: "Success",
            text: message,
            confirmButtonColor: "#28a745",
        });
    },

    confirm: (message) => {
        return Swal.fire({
            icon: "question",
            title: "Confirm",
            text: message,
            showCancelButton: true,
            confirmButtonColor: "#28a745",
            cancelButtonColor: "#dc3545",
            confirmButtonText: "Yes",
            cancelButtonText: "No",
        });
    },

    loading: () => {
        Swal.fire({
            title: "Processing...",
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            },
        });
    },
};

// Enhanced error handler for AJAX requests
const handleAjaxError = (xhr, status, error) => {
    console.error("Error:", error);

    if (xhr.status === 422) {
        const validationErrors = xhr.responseJSON.errors;
        let errorMessages = Object.values(validationErrors).flat().join("\n");

        notifications.error(
            errorMessages || "Validation failed. Please check your inputs."
        );
    } else {
        notifications.error("An unexpected error occurred. Please try again.");
    }
};

// Store for managing invoice items
const invoiceItemsStore = {
    items: [],

    addItem(item) {
        this.items.push(item);
        this.updateTable();
        this.updateTotals();
    },

    removeItem(index) {
        this.items.splice(index, 1);
        this.updateTable();
        this.updateTotals();
    },

    updateItem(index, updatedItem) {
        this.items[index] = updatedItem;
        this.updateTable();
        this.updateTotals();
    },

    getItemsForSubmission() {
        return this.items.map((item) => ({
            type: item.type,
            item_id: item.itemId,
            code: item.code,
            provider_id: item.providerId,
            quantity: item.quantity,
            price: item.price,
            discount: item.discount,
            tax: item.tax,
        }));
    },

    updateTable() {
        const tbody = $("#invoice-items tbody");
        tbody.empty();

        this.items.forEach((item, index) => {
            const row = $(`
                <tr>
                    <td>${item.itemName}</td>
                    <td>${item.code}</td>
                    <td>${item.providerName}</td>
                    <td>${item.quantity}</td>
                    <td>$${item.price.toFixed(2)}</td>
                    <td>${item.discount}</td>
                    <td>${item.tax}</td>
                    <td>$${item.due.toFixed(2)}</td>
                    <td>
                        <button class="btn btn-sm btn-warning edit-item me-1" data-index="${index}">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-danger delete-item" data-index="${index}">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            `);
            tbody.append(row);
        });
    },

    updateTotals() {
        // Calculate totals for services and products separately
        let servicesTotal = 0;
        let productsTotal = 0;
        let discountTotal = 0;
        let taxTotal = 0;

        this.items.forEach((item) => {
            const subtotal = item.quantity * item.price;
            const discountAmount = (subtotal * item.discount) / 100;
            const taxableAmount = subtotal - discountAmount;
            const taxAmount = (taxableAmount * item.tax) / 100;

            if (item.type === "service") {
                servicesTotal += taxableAmount;
            } else {
                productsTotal += taxableAmount;
            }

            discountTotal += discountAmount;
            taxTotal += taxAmount;
        });

        // Update payment summary
        $("#services-total").text(`$${servicesTotal.toFixed(2)}`);
        $("#products-total").text(`$${productsTotal.toFixed(2)}`);
        $("#discount-total").text(`-$${discountTotal.toFixed(2)}`);
        $("#tax-total").text(`$${taxTotal.toFixed(2)}`);

        const grandTotal = servicesTotal + productsTotal + taxTotal;
        $("#grand-total").text(`$${grandTotal.toFixed(2)}`);

        // Update net total (grand total - deposit)
        const deposit = parseFloat($("#deposit-input").val()) || 0;
        const netTotal = grandTotal - deposit;
        $("#net-total").text(`$${netTotal.toFixed(2)}`);

        // Store the grand total for later calculations
        this.grandTotal = grandTotal;
    },
};

// Enhanced helper functions with loading states
function loadCategories(type) {
    const $category = $("#category");
    $category
        .prop("disabled", true)
        .html(
            '<option value="" selected disabled>Loading categories...</option>'
        );

    $.ajax({
        url: API_ENDPOINTS.CATEGORIES,
        method: "GET",
        data: { type: type },
        beforeSend: () => notifications.loading(),
        success: function (response) {
            Swal.close();
            $category.html(
                '<option value="" selected disabled>Select Category</option>'
            );
            response.forEach((category) => {
                $category.append(new Option(category.name, category.id));
            });
            $category.prop("disabled", false);
        },
        error: handleAjaxError,
    });
}

// Enhanced checkout handler with improved validation and response handling
function handleCheckout(e) {
    e.preventDefault();

    if (invoiceItemsStore.items.length === 0) {
        notifications.error("Please add at least one item to the invoice");
        return;
    }

    const data = {
        customer_id: $("#customer_id").val(),
        deposit: parseFloat($("#deposit-input").val()) || 0,
        payment_method_id: $("#payment_method_id").val(),
        payment_method_value: parseFloat($("#payment-method-value").val()) || 0,
        branch_id: $("#branch_id").val(),
        invoice_date: $("#invoice_date").val(),
        items: invoiceItemsStore.getItemsForSubmission(),
        status: $("#status").val(),
        cash_payment: parseFloat($("#cash-value").val()) || 0,
    };

    $.ajax({
        url: API_ENDPOINTS.INVOICE_STORE,
        type: "POST",
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
        contentType: "application/json",
        data: JSON.stringify(data),
         beforeSend: () => notifications.loading(),
        success: function (response) {
            Swal.fire({
                icon: "success",
                title: "Invoice Created Successfully",
                text: "What would you like to do next?",
                showDenyButton: true,
                showCancelButton: true,
                confirmButtonText: "Print Invoice",
                denyButtonText: "Create New Invoice",
                cancelButtonText: "Stay Here",
            }).then((result) => {
                console.log(result);

                if (result.isConfirmed) {
                    window.open(`invoice/${response.invoice_id}`, "_blank");
                     $("#customer_id").val("").trigger("change");
                     invoiceItemsStore.items = [];
                     invoiceItemsStore.updateTable();
                     invoiceItemsStore.updateTotals();
                } else if (result.isDenied) {
                    window.location.href = route("sales_invoices.create");
                } else {
                    // Reset form if staying on page
                    resetForm();
                    $("#customer_id").val("").trigger("change");
                    invoiceItemsStore.items = [];
                    invoiceItemsStore.updateTable();
                    invoiceItemsStore.updateTotals();
                }
            });
        },
        error: function (xhr, status, error) {
            if (xhr.status === 422) {
                const validationErrors = xhr.responseJSON.errors;
                let errorMessages = "";
                for (const field in validationErrors) {
                    if (validationErrors.hasOwnProperty(field)) {
                        errorMessages += `${validationErrors[field].join(
                            "\n"
                        )}\n`;
                    }
                }

                Swal.fire({
                    icon: "error",
                    title: "Validation Error",
                    text:
                        errorMessages ||
                        "Please correct the errors and try again.",
                    confirmButtonColor: "#dc3545",
                });
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: "An unexpected error occurred. Please try again.",
                    confirmButtonColor: "#dc3545",
                });
            }
            console.error("Checkout error:", error);
        },
    });
}

function loadItems(type, categoryId) {
    const $item = $("#item");
    $item
        .prop("disabled", true)
        .html('<option value="" selected disabled>Loading items...</option>');

    $.ajax({
        url: API_ENDPOINTS.ITEMS,
        method: "GET",
        data: {
            type: type,
            category_id: categoryId,
        },
        success: function (response) {
            $item.html(
                '<option value="" selected disabled>Select Item</option>'
            );
            response.forEach((item) => {
                const option = new Option(item.name, item.id);
                $(option).data("code", item.code ?? toString(item.id));
                $item.append(option);
            });
            $item.prop("disabled", false);
        },
        error: function (xhr, status, error) {
            console.error("Error loading items:", error);
            notifications.error("Failed to load items");
        },
    });
}

function loadItemDetails(type, itemId) {
    $.ajax({
        url: API_ENDPOINTS.ITEM_DETAILS(itemId),
        method: "GET",
        data: { type: type },
        success: function (response) {
            $("#price").val(response.price);
            if (!response.price_can_change) {
                $("#price").prop("readonly", true);
            } else {
                $("#price").prop("readonly", false);
            }
        },
        error: function (xhr, status, error) {
            console.error("Error loading item details:", error);
            notifications.error("Failed to load item details");
        },
    });
}

function loadProviders(type, itemId) {
    const $provider = $("#provider");
    $provider
        .prop("disabled", true)
        .html(
            '<option value="" selected disabled>Loading providers...</option>'
        );

    $.ajax({
        url: API_ENDPOINTS.PROVIDERS,
        method: "GET",
        data: {
            item_type: type,
            item_id: itemId,
        },
        success: function (data) {
            $provider.html(
                '<option value="" selected disabled>Select Provider</option>'
            );
            data.forEach((employee) => {
                $provider.append(new Option(employee.name, employee.id));
            });
            $provider.prop("disabled", false);
        },
        error: function (xhr, status, error) {
            console.error("Error loading providers:", error);
            notifications.error("Failed to load providers");
        },
    });
}

function calculateDue() {
    const quantity = parseFloat($("#quantity").val());
    const price = parseFloat($("#price").val());
    const discount = parseFloat($("#discount").val());
    const tax = parseFloat($("#tax").val());

    const subtotal = quantity * price;
    const discountAmount = (subtotal * discount) / 100;
    const taxableAmount = subtotal - discountAmount;
    const taxAmount = (taxableAmount * tax) / 100;

    return taxableAmount + taxAmount;
}

function resetDependentFields() {
    $("#category")
        .prop("disabled", true)
        .html('<option value="" selected disabled>Select Category</option>');
    $("#item")
        .prop("disabled", true)
        .html('<option value="" selected disabled>Select Item</option>');
    $("#provider")
        .prop("disabled", true)
        .html('<option value="" selected disabled>Select Provider</option>');
    $("#price").val("").prop("readonly", true);
}

function resetForm() {
    // Instead of using form.reset(), reset each field individually
    $("#item-type").val("").trigger("change");
    $("#category").val("").prop("disabled", true);
    $("#item").val("").prop("disabled", true);
    $("#provider").val("").prop("disabled", true);
    $("#quantity").val("1");
    $("#price").val("").prop("readonly", true);
    $("#discount").val("0");
    $("#tax").val("14");
}

function validateItemForm() {
    const requiredFields = [
        { id: "item-type", name: "Item Type" },
        { id: "category", name: "Category" },
        { id: "item", name: "Item" },
        { id: "provider", name: "Provider" },
        { id: "quantity", name: "Quantity" },
        { id: "price", name: "Price" },
    ];

    for (const field of requiredFields) {
        const value = $(`#${field.id}`).val();
        if (!value || value.trim() === "") {
            notifications.error(`${field.name} is required`);
            return false;
        }
    }

    if (parseFloat($("#quantity").val()) <= 0) {
        notifications.error("Quantity must be greater than 0");
        return false;
    }

    if (parseFloat($("#price").val()) <= 0) {
        notifications.error("Price must be greater than 0");
        return false;
    }

    const discount = parseFloat($("#discount").val());
    if (discount < 0 || discount > 100) {
        notifications.error("Discount must be between 0 and 100");
        return false;
    }

    const tax = parseFloat($("#tax").val());
    if (tax < 0 || tax > 100) {
        notifications.error("Tax must be between 0 and 100");
        return false;
    }

    return true;
}

$(document).ready(function () {
    // Set a global variable to track the current AJAX request URL
    let currentAjaxUrl = null;

    // Add loading state to all AJAX requests
    $(document)
        .ajaxStart(function () {
            notifications.loading(); // Show loading notification
        })
        .ajaxStop(function () {
            if (
                currentAjaxUrl &&
                currentAjaxUrl !== API_ENDPOINTS.INVOICE_STORE
            ) {
                Swal.close(); // Close Swal dialog if it's not the invoice store URL
            }
        });

    // You can update the currentAjaxUrl dynamically for each AJAX request
    $(document).ajaxSend(function (event, jqXHR, ajaxSettings) {
        // Store the URL of the current AJAX request
        currentAjaxUrl = ajaxSettings.url;
    });

    // Attach enhanced event handlers
    $("#checkout").click(handleCheckout);

    // Delete item handler with confirmation
    $(document).on("click", ".delete-item", function () {
        const index = $(this).data("index");
        notifications
            .confirm("Are you sure you want to remove this item?")
            .then((result) => {
                if (result.isConfirmed) {
                    invoiceItemsStore.removeItem(index);
                }
            });
    });

    // Item type change
    $("#item-type").change(function () {
        const type = $(this).val();
        if (type) {
            loadCategories(type);
            resetDependentFields();
        }
    });

    // Category change
    $("#category").change(function () {
        const categoryId = $(this).val();
        const type = $("#item-type").val();
        if (categoryId) {
            loadItems(type, categoryId);
        }
    });

    // Item selection
    $("#item").change(function () {
        const itemId = $(this).val();
        const type = $("#item-type").val();
        if (itemId) {
            loadItemDetails(type, itemId);
            loadProviders(type, itemId);
        }
    });

    // Add item button
    $("#add-item-btn").click(function (e) {
        e.preventDefault();

        if (!validateItemForm()) {
            return;
        }

        const item = {
            type: $("#item-type").val(),
            categoryId: $("#category").val(),
            categoryName: $("#category option:selected").text(),
            itemId: $("#item").val(),
            itemName: $("#item option:selected").text(),
            code: $("#item").find(":selected").data("code"),
            providerId: $("#provider").val(),
            providerName: $("#provider option:selected").text(),
            quantity: parseFloat($("#quantity").val()),
            price: parseFloat($("#price").val()),
            discount: parseFloat($("#discount").val()),
            tax: parseFloat($("#tax").val()),
            due: calculateDue(),
        };

        invoiceItemsStore.addItem(item);
        resetForm();
    });

    // Edit item handler
    $(document).on("click", ".edit-item", function () {
        const index = $(this).data("index");
        const item = invoiceItemsStore.items[index];

        // Populate form with item data
        $("#item-type").val(item.type).trigger("change");
        // Wait for categories to load
        setTimeout(() => {
            $("#category").val(item.categoryId).trigger("change");
            // Wait for items to load
            setTimeout(() => {
                $("#item").val(item.itemId).trigger("change");
                // Wait for providers to load
                setTimeout(() => {
                    $("#provider").val(item.providerId);
                    $("#quantity").val(item.quantity);
                    $("#price").val(item.price);
                    $("#discount").val(item.discount);
                    $("#tax").val(item.tax);
                }, 500);
            }, 500);
        }, 500);

        // Remove the item from the store
        invoiceItemsStore.removeItem(index);
    });

    // Delete item handler
    $(document).on("click", ".delete-item", function () {
        const index = $(this).data("index");
        if (confirm("Are you sure you want to remove this item?")) {
            invoiceItemsStore.removeItem(index);
        }
    });

    // Format only specific number inputs on blur
    const numberInputs = [
        "#deposit-input",
        "#payment-method-value",
        "#cash-value",
        "#price",
        "#quantity",
        "#discount",
        "#tax",
        "#customer-deposit",
    ];

    $(numberInputs.join(", ")).on("blur", function () {
        const value = parseFloat($(this).val()) || 0;
        $(this).val(value.toFixed(2));
    });

    $("#deposit-input").on("input", function () {
        const value = parseFloat($(this).val()) || 0;
        if (value < 0) {
            $(this).val("0.00");
        }
        invoiceItemsStore.updateTotals();
    });

    // Handle payment method value changes
    $("#payment-method-value").on("input", function () {
        const value = parseFloat($(this).val()) || 0;
        const grandTotal = invoiceItemsStore.grandTotal || 0;
        const deposit = parseFloat($("#deposit-input").val()) || 0;
        const netTotal = grandTotal - deposit;

        if (value > netTotal) {
            $(this).val(netTotal.toFixed(2));
        }

        // Update cash value
        const paymentMethodValue = parseFloat($(this).val()) || 0;
        const remainingBalance = netTotal - paymentMethodValue;
        $("#cash-value").val(remainingBalance.toFixed(2));
    });

    // Handle cash value changes
    $("#cash-value").on("input", function () {
        const value = parseFloat($(this).val()) || 0;
        const grandTotal = invoiceItemsStore.grandTotal || 0;
        const deposit = parseFloat($("#deposit-input").val()) || 0;
        const paymentMethodValue =
            parseFloat($("#payment-method-value").val()) || 0;
        const maxCash = grandTotal - deposit - paymentMethodValue;

        if (value > maxCash) {
            $(this).val(maxCash.toFixed(2));
        }
    });
});
