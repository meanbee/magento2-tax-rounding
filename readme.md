# Magento 2 - Tax Rounding Issues

We experienced an issue where by a store configured with ex-VAT prices in the backend and a frontend display of prices including tax would result in incorrectly rounding tax amounts.

Our solution was to override the tax calculation to perform rounding to three decimal places.

Please use at your own risk.
