import type { Locator, Page, Response } from "@playwright/test";
import { BasePage } from "../BasePage";

export class SuggestionSearchbarPage extends BasePage {
    constructor(page: Page) {
        super(page);
    }

    async open(): Promise<Response | null> {
        return this.visit("");
    }

    private searchInput(): Locator {
        return this.page.getByPlaceholder("Search products here").first();
    }

    private dropdown(): Locator {
        return this.page.locator("#suggest");
    }

    private resultRows(): Locator {
        return this.dropdown().locator(":scope a:has(div.min-w-0)");
    }

    private rowByName(productName: string): Locator {
        return this.resultRows().filter({ hasText: productName });
    }

    private rowHighlightedTerm(productName: string): Locator {
        return this.rowByName(productName).locator("p span.font-semibold").first();
    }

    private rowCategoryLine(productName: string): Locator {
        return this.rowByName(productName).locator("p.text-zinc-500").first();
    }

    private showAllLink(): Locator {
        return this.dropdown().locator(":scope a:not(:has(div.min-w-0))").last();
    }

    async type(text: string): Promise<void> {
        const input = this.searchInput();

        await input.click();
        await input.fill("");
        await input.pressSequentially(text, { delay: 100 });
    }

    async waitForDropdown(): Promise<void> {
        await this.dropdown().waitFor({ state: "visible" });
    }

    async expectProductRow(productName: string): Promise<Locator> {
        const row = this.rowByName(productName);

        await row.waitFor({ state: "visible" });

        return row;
    }

    async readHighlightedTerm(productName: string): Promise<string> {
        const text = await this.rowHighlightedTerm(productName).innerText();

        return text.trim();
    }

    async readCategoryLine(productName: string): Promise<string> {
        const line = this.rowCategoryLine(productName);

        await line.waitFor({ state: "visible" });

        return (await line.innerText()).trim();
    }

    async openProductRow(productName: string): Promise<void> {
        await this.rowByName(productName).click();
    }

    async readShowAllLabel(): Promise<string> {
        return (await this.showAllLink().innerText()).trim();
    }

    async dropdownIsVisible(): Promise<boolean> {
        return this.dropdown().isVisible();
    }
}
