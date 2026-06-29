import type { Page, Response } from "@playwright/test";
import { BasePage } from "../BasePage";

export class HomePage extends BasePage {
    constructor(page: Page) {
        super(page);
    }

    async open(): Promise<Response | null> {
        return this.visit("");
    }
}
