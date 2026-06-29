import type { Locator, Page, Response } from "@playwright/test";
import { BasePage } from "../../../BasePage";

export type SimpleProductDraft = {
    name: string;
    sku: string;
    productNumber: string;
    shortDescription: string;
    description: string;
    price: string;
    weight: string;
    inventory: string;
    attributeFamilyId: string;
    categoryName?: string;
};

export class ProductCreatePage extends BasePage {
    constructor(page: Page) {
        super(page);
    }

    async openCreateForm(): Promise<Response | null> {
        const response = await this.visit("admin/catalog/products");

        await this.openCreateButton().waitFor({ state: "visible" });
        await this.openCreateButton().click();
        await this.typeSelect().waitFor({ state: "visible" });

        return response;
    }

    private openCreateButton(): Locator {
        return this.page.getByRole("button", { name: "Create Product" });
    }

    private typeSelect(): Locator {
        return this.page.locator('select[name="type"]');
    }

    private attributeFamilySelect(): Locator {
        return this.page.locator('select[name="attribute_family_id"]');
    }

    private skuInput(): Locator {
        return this.page.locator('input[name="sku"]');
    }

    private firstSaveButton(): Locator {
        return this.page.getByRole("button", { name: "Save Product" });
    }

    private editFormReady(): Locator {
        return this.page.locator(
            'button.primary-button:has-text("Save Product")',
        );
    }

    private productNumberInput(): Locator {
        return this.page.locator("#product_number");
    }

    private nameInput(): Locator {
        return this.page.locator("#name");
    }

    private metaTitleInput(): Locator {
        return this.page.locator("#meta_title");
    }

    private metaKeywordsInput(): Locator {
        return this.page.locator("#meta_keywords");
    }

    private metaDescriptionInput(): Locator {
        return this.page.locator("#meta_description");
    }

    private priceInput(): Locator {
        return this.page.locator("#price");
    }

    private weightInput(): Locator {
        return this.page.locator("#weight");
    }

    private guestCheckoutCheckbox(): Locator {
        return this.page.locator(".peer.h-5").first();
    }

    private inventoryInput(): Locator {
        return this.page.locator('input[name="inventories[1]"]');
    }

    private categoryCheckboxLabel(name: string): Locator {
        return this.page
            .locator("div.v-tree-item-wrapper")
            .locator("label", { hasText: name })
            .locator("span")
            .first();
    }

    private successFlash(): Locator {
        return this.page.getByText("Product updated successfully").first();
    }

    async createSimpleProduct(draft: SimpleProductDraft): Promise<void> {
        await this.openCreateForm();

        await this.typeSelect().selectOption("simple");
        await this.attributeFamilySelect().selectOption(draft.attributeFamilyId);
        await this.skuInput().fill(draft.sku);

        await Promise.all([
            this.page.waitForURL(/\/admin\/catalog\/products\/edit\/\d+/, {
                timeout: 30_000,
            }),
            this.firstSaveButton().click(),
        ]);

        await this.editFormReady().waitFor({ state: "visible" });

        await this.productNumberInput().fill(draft.productNumber);
        await this.nameInput().fill(draft.name);

        await this.fillTinymce("#short_description_ifr", draft.shortDescription);
        await this.fillTinymce("#description_ifr", draft.description);

        await this.metaTitleInput().fill(draft.name);
        await this.metaKeywordsInput().fill(draft.name);
        await this.metaDescriptionInput().fill(draft.shortDescription);

        await this.priceInput().fill(draft.price);
        await this.weightInput().fill(draft.weight);

        if (!(await this.guestCheckoutCheckbox().isChecked())) {
            await this.guestCheckoutCheckbox().click();
        }

        await this.inventoryInput().fill(draft.inventory);

        if (draft.categoryName) {
            await this.categoryCheckboxLabel(draft.categoryName).click();
        }

        await this.firstSaveButton().click();
        await this.successFlash().waitFor({
            state: "visible",
            timeout: 30_000,
        });
    }

    private async fillTinymce(
        iframeSelector: string,
        content: string,
    ): Promise<void> {
        await this.page.waitForSelector(iframeSelector, { state: "attached" });

        const iframe = this.page.frameLocator(iframeSelector);
        const editorBody = iframe.locator("body");

        await editorBody.waitFor({ state: "visible", timeout: 15_000 });

        await editorBody.click();
        await editorBody.press("Control+a");
        await editorBody.press("Backspace");
        await editorBody.pressSequentially(content);
    }
}
