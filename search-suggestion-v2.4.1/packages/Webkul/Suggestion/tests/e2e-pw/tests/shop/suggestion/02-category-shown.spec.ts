import { test, expect } from "../../../fixtures/test";
import { SuggestionSearchbarPage } from "../../../pages/shop/SuggestionSearchbarPage";
import { readRuntimeState } from "../../../utils/runtimeState";

test.describe("Shop — suggestion", () => {
    test("dropdown row exposes the attached category name", async ({ page }) => {
        const product = readRuntimeState<{ name: string }>("suggestion-product");
        const category = readRuntimeState<{ name: string }>(
            "suggestion-category",
        );

        const searchbar = new SuggestionSearchbarPage(page);
        await searchbar.open();
        await searchbar.type(product.name.slice(0, 4));
        await searchbar.waitForDropdown();
        await searchbar.expectProductRow(product.name);

        const categoryLine = await searchbar.readCategoryLine(product.name);

        expect(
            categoryLine,
            `Expected category line under "${product.name}" to mention "${category.name}", got "${categoryLine}".`,
        ).toContain(category.name);
    });
});
