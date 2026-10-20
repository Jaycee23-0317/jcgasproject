function printReceipt(orderData) {
    const branchID = orderData.BranchID || 'N/A';
    const branchName = orderData.BranchName || 'N/A';
    const branchAddress = orderData.BranchAddress || 'N/A';
    const branchEmail = orderData.BranchEmail || 'N/A';
    const branchContactNumber = orderData.BranchContactNumber || 'N/A';
    const orderID = orderData.OrderID || 'N/A';
    const transactionID = orderData.TransactionID || 'N/A';
    const customerName = orderData.CustomerName || 'N/A';
    const address = orderData.DeliveryAddress || 'N/A';

    const paymentMethod =
        orderData.PaymentMethod || 'N/A';

    const orderDate =
        orderData.OrderDate || new Date().toISOString();

    // Make sure items is an array
    const items = Array.isArray(orderData.items)
        ? orderData.items
        : [];


    // =========================================================
    // FORMATTERS
    // =========================================================

    function formatMoney(amount) {
        return Number(amount).toLocaleString('en-PH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function formatDate(date) {
        const parsedDate = new Date(date);

        if (isNaN(parsedDate)) {
            return 'N/A';
        }

        return parsedDate.toLocaleString('en-PH', {
            year: 'numeric',
            month: 'short',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    let totalAmount = 0;

    const itemsHTML = items.length
        ? items.map(item => {
            const productName =
                item.ProductName ||
                'N/A';

            const quantity = parseInt(
                item.Quantity || 0
            );

            const unitPrice = parseFloat(
                item.OrderedPrice ||
                0
            );

            const itemTotal = quantity * unitPrice;

            totalAmount += itemTotal;

            return `
                <tr>
                    <td>
                        ${productName}
                    </td>

                    <td class="right">
                        ${quantity}
                    </td>

                    <td class="right">
                        ₱${formatMoney(unitPrice)}
                    </td>

                    <td class="right">
                        ₱${formatMoney(itemTotal)}
                    </td>
                </tr>
            `;
        }).join('')
        : `
            <tr>
                <td colspan="4" class="no-items">
                    No items found
                </td>
            </tr>
        `;


    const receiptHTML = `
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Transaction #${transactionID}</title>
            <link rel="stylesheet" href="../css/receipt.css">
        </head>

        <body>
            <div class="receipt">

                <div class="header">
                    <h1>${branchName}</h1>
                    <p>${branchAddress}</p>
                    <p>${branchContactNumber}</p>
                </div>


                <!-- RECEIPT TITLE -->

                <div class="receipt-title">
                    RECEIPT
                </div>


                <!-- ORDER INFORMATION -->

                <div class="info">

                    <div class="info-row">
                        <span class="info-label">
                            Transaction #
                        </span>

                        <span class="info-value">
                            ${transactionID}
                        </span>
                    </div>

                    <div class="info-row">
                        <span class="info-label">
                            Date
                        </span>

                        <span class="info-value">
                            ${formatDate(orderDate)}
                        </span>
                    </div>

                    <div class="info-row">
                        <span class="info-label">
                            Payment
                        </span>

                        <span class="info-value">
                            ${paymentMethod}
                        </span>
                    </div>

                </div>


                <div class="divider"></div>


                <!-- CUSTOMER INFORMATION -->

                <div class="info">

                    <div class="info-row">
                        <span class="info-label">
                            Customer
                        </span>

                        <span class="info-value">
                            ${customerName}
                        </span>
                    </div>

                    <div class="info-row">
                        <span class="info-label">
                            Address
                        </span>

                        <span class="info-value">
                            ${address}
                        </span>
                    </div>

                </div>


                <div class="divider"></div>


                <!-- ITEMS -->

                <table class="items">

                    <thead>
                        <tr>
                            <th>Item</th>
                            <th class="right">Qty</th>
                            <th class="right">Price</th>
                            <th class="right">Amount</th>
                        </tr>
                    </thead>

                    <tbody>
                        ${itemsHTML}
                    </tbody>

                </table>


                <div class="divider"></div>


                <!-- TOTAL -->

                <div class="total">
                    <span>TOTAL</span>

                    <span>
                        ₱${formatMoney(totalAmount)}
                    </span>
                </div>


                <!-- FOOTER -->

                <div class="footer">

                    <div class="thank-you">
                        Thank you for your order!
                    </div>

                    <div>
                        JC Gas — Safe, Reliable, Delivered.
                    </div>

                </div>

            </div>


            <script>
                window.onload = function() {
                    window.print();
                };
            <\/script>

        </body>
        </html>
    `;


    // =========================================================
    // OPEN PRINT WINDOW
    // =========================================================

    const printWindow = window.open(
        '',
        '_blank',
        'width=500,height=700'
    );

    if (!printWindow) {
        showAlertBanner(
            'Please allow pop-ups to print the receipt.',
            'warning'
        );
        return;
    }

    printWindow.document.open();
    printWindow.document.write(receiptHTML);
    printWindow.document.close();
}