import { test, expect } from "../../../../fixtures/test";
import { ProductCreatePage } from "../../../../pages/admin/catalog/products/ProductCreatePage";
import {
    readRuntimeState,
    writeRuntimeState,
} from "../../../../utils/runtimeState";
import productFixture from "../../../../data/catalog/products/1/simple.json";

test.describe("Admin — catalog › products", () => {
    test("create suggestion-eligible simple product in the seeded category", async ({
        page,
    }) => {
        test.slow();

        const category = readRuntimeState<{ name: string }>(
            "suggestion-category",
        );

        const create = new ProductCreatePage(page);

        const stamp = Date.now();
        const name = `${productFixture.namePrefix}-${stamp}`;
        const sku = `SUG${stamp}`;
        const productNumber = `SUG-PN-${stamp}`;

        await create.createSimpleProduct({
            name,
            sku,
            productNumber,
            shortDescription: productFixture.shortDescription,
            description: productFixture.description,
            price: productFixture.price,
            weight: productFixture.weight,
            inventory: productFixture.inventory,
            attributeFamilyId: productFixture.attributeFamilyId,
            categoryName: category.name,
        });

        writeRuntimeState("suggestion-product", {
            name,
            sku,
            price: productFixture.price,
        });

        expect(name).toContain(productFixture.namePrefix);
    });
});
