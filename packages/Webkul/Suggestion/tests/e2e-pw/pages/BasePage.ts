import path from "path";
import type { Page, Response } from "@playwright/test";
import { DATA_PATH } from "../utils/paths";

export abstract class BasePage {
    constructor(protected readonly page: Page) {}

    protected async visit(urlPath: string = ""): Promise<Response | null> {
        const normalized = urlPath.replace(/^\/+/, "");

        return this.page.goto(normalized);
    }

    protected dataPath(relativePath: string): string {
        return path.join(DATA_PATH, relativePath);
    }
}
