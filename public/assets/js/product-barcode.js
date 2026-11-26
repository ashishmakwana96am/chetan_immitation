function findProductScanMatch(products, query) {
    const code = query.trim().toLowerCase();
    if (!code) return null;

    for (const product of products) {
        const variant = (product.variants || []).find(item =>
            item.barcode && item.barcode.trim().toLowerCase() === code
        );
        if (variant) return { product, variantId: variant.id };

        if (product.barcode && String(product.barcode).trim().toLowerCase() === code) {
            return { product, variantId: null };
        }
    }

    const matches = products.filter(product => product.name.toLowerCase() === code);
    return matches.length === 1 ? { product: matches[0], variantId: null } : null;
}
