import type { Locator, Page, Response } from "@playwright/test";
import { BasePage } from "../../../BasePage";

export type CategoryDraft = {
    name: string;
    position: string;
    displayMode: string;
    metaTitle: string;
    metaKeywords: string;
    metaDescription: string;
    filterableAttributes: string[];
};

export class CategoryCreatePage extends BasePage {
    constructor(page: Page) {
        super(page);
    }

    async open(): Promise<Response | null> {
        const response = await this.visit("admin/catalog/categories");

        await this.createButton().waitFor({ state: "visible" });
        await this.createButton().click();

        await this.page.waitForURL(/\/admin\/catalog\/categories\/create/, {
            timeout: 30_000,
        });
        await this.nameInput().waitFor({ state: "visible" });
        await this.page.waitForLoadState("networkidle");
        await this.page.waitForTimeout(500);

        return response;
    }

    private createButton(): Locator {
        return this.page.locator("div.primary-button").first();
    }

    private nameInput(): Locator {
        return this.page.locator('input[name="name"]');
    }

    private rootLabel(): Locator {
        return this.page.locator('label:has-text("Root")');
    }

    private positionInput(): Locator {
        return this.page.locator('input[name="position"]');
    }

    private displayModeSelect(): Locator {
        return this.page.locator('select[name="display_mode"]');
    }

    private statusToggleLabel(): Locator {
        return this.page.locator('label[for="status"]');
    }

    private statusInput(): Locator {
        return this.page.locator('input[type="checkbox"][name="status"]');
    }

    private metaTitleInput(): Locator {
        return this.page.locator('input[name="meta_title"]');
    }

    private slugInput(): Locator {
        return this.page.locator('input[name="slug"]');
    }

    private metaKeywordsInput(): Locator {
        return this.page.locator('input[name="meta_keywords"]');
    }

    private metaDescriptionInput(): Locator {
        return this.page.locator('textarea[name="meta_description"]');
    }

    private filterableAttributeLabel(name: string): Locator {
        return this.page.locator(`label.icon-uncheckbox[for="${name}"]`);
    }

    private filterableAttributeInput(name: string): Locator {
        return this.page.locator(`input[type="checkbox"][id="${name}"]`);
    }

    private saveButton(): Locator {
        return this.page.getByRole("button", { name: "Save Category" });
    }

    private successFlash(): Locator {
        return this.page.getByText("Category created successfully.").first();
    }

    async create(draft: CategoryDraft): Promise<void> {
        await this.typeIntoVField(this.nameInput(), draft.name);
        await this.rootLabel().click();

        await this.positionInput().fill(draft.position);
        await this.displayModeSelect().selectOption(draft.displayMode);

        if (!(await this.statusInput().isChecked())) {
            await this.statusToggleLabel().click();
        }

        await this.typeIntoVField(this.metaTitleInput(), draft.metaTitle);
        await this.typeIntoVField(this.slugInput(), draft.name);
        await this.metaKeywordsInput().fill(draft.metaKeywords);
        await this.metaDescriptionInput().fill(draft.metaDescription);

        for (const attr of draft.filterableAttributes) {
            const checkbox = this.filterableAttributeInput(attr);

            if (!(await checkbox.isChecked())) {
                await this.filterableAttributeLabel(attr).click();
            }
        }

        await this.saveButton().click();
        await this.successFlash().waitFor({
            state: "visible",
            timeout: 30_000,
        });
    }

    private async typeIntoVField(input: Locator, value: string): Promise<void> {
        await input.click();
        await input.fill("");
        await input.pressSequentially(value, { delay: 30 });
        await input.press("Tab");
    }
}
