import { test, expect } from "../../../../fixtures/test";
import { CategoryCreatePage } from "../../../../pages/admin/catalog/categories/CategoryCreatePage";
import { writeRuntimeState } from "../../../../utils/runtimeState";
import categoryFixture from "../../../../data/catalog/categories/1/basic.json";

test.describe("Admin — catalog › categories", () => {
    test("create suggestion-eligible category", async ({ page }) => {
        const create = new CategoryCreatePage(page);

        const name = `${categoryFixture.namePrefix}-${Date.now()}`;

        await create.open();
        await create.create({
            name,
            position: categoryFixture.position,
            displayMode: categoryFixture.displayMode,
            metaTitle: categoryFixture.metaTitle,
            metaKeywords: categoryFixture.metaKeywords,
            metaDescription: categoryFixture.metaDescription,
            filterableAttributes: categoryFixture.filterableAttributes,
        });

        writeRuntimeState("suggestion-category", { name });

        expect(name.length).toBeGreaterThan(0);
    });
});
