# Monthly Sales Report - Testing Instructions

## How It Works

On the **1st of every month**, a download link will automatically appear at the top of the Sales List page. This link allows you to download a PDF report containing all transactions from the previous month.

## Features

- **Automatic Detection**: The system automatically detects if today is the 1st of the month
- **Previous Month Data**: Shows all transactions from the 1st to the last day of the previous month
- **Complete Details**: Includes:
  - All transaction details (date, receipt ID, products, room details, quantities, prices, discounts, totals, cashier)
  - Product summary (all products sold with total quantities and amounts)
  - Grand total of all sales for the month
- **Downloadable PDF**: Click the button to download the report as a PDF file

## How to Test (Since We're Not on the 1st Yet)

### Method 1: Temporarily Modify the Code (Recommended for Testing)

1. Open `app/Http/Controllers/PenjualanController.php`
2. Find the `index()` method (around line 14)
3. Temporarily change this line:
   ```php
   $isFirstOfMonth = (date('d') == '01');
   ```
   To:
   ```php
   $isFirstOfMonth = true; // For testing - always show the link
   ```
4. Save the file
5. Visit the Sales List page - you should see the download link
6. Click the download button to test the PDF generation
7. **Remember to change it back** after testing!

### Method 2: Test the PDF Directly

You can test the PDF generation directly by visiting this URL in your browser:

```
http://your-domain/penjualan/monthly-report?startDate=2024-12-01&endDate=2024-12-31
```

Replace:
- `your-domain` with your actual domain (e.g., `localhost:8000` or your hosted URL)
- `startDate` and `endDate` with the actual dates you want to test (use a month where you have transactions)

### Method 3: Change Your System Date (Not Recommended)

You can temporarily change your computer's system date to the 1st of a month, but this is not recommended as it may affect other applications.

## What the PDF Contains

1. **Header**: Monthly Sales Report title with month name and date range
2. **Transaction Details Table**: 
   - Transaction number
   - Date
   - Receipt ID
   - Products (up to 3, with count if more)
   - Room Details
   - Quantity
   - Total Price
   - Discount
   - Total Pay
   - Cashier name

3. **Product Summary Table**:
   - Product name
   - Total quantity sold
   - Total amount for that product

4. **Grand Total Summary**:
   - Total number of transactions
   - Total items sold
   - **GRAND TOTAL** (sum of all sales)

## Notes

- Only completed transactions are included (where `total_item > 0` and `bayar > 0`)
- The report is generated on-demand when you click the download button
- The PDF filename will be: `Monthly-Sales-Report-[Month-Name]-[Year].pdf`
- The link only appears on the 1st of each month automatically
- Both admin and cashiers can access and download the report

