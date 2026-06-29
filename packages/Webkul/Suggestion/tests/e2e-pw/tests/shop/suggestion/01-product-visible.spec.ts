import { test, expect } from "../../../fixtures/test";
import { SuggestionSearchbarPage } from "../../../pages/shop/SuggestionSearchbarPage";
import { readRuntimeState } from "../../../utils/runtimeState";

test.describe("Shop — suggestion", () => {
    test("dropdown shows the simple product when typing its name", async ({
        page,
    }) => {
        const product = readRuntimeState<{ name: string }>("suggestion-product");

        const searchbar = new SuggestionSearchbarPage(page);
        await searchbar.open();

        const term = product.name.slice(0, 4);

        await searchbar.type(term);
        await searchbar.waitForDropdown();

        await searchbar.expectProductRow(product.name);

        const highlighted = await searchbar.readHighlightedTerm(product.name);

        expect(
            highlighted.toLowerCase(),
            `Expected the matched substring "${term}" to be wrapped in <span class="font-semibold"> inside the dropdown row, got "${highlighted}".`,
        ).toBe(term.toLowerCase());
    });
});
