import fs from "fs";
import path from "path";
import { STATE_DIR_PATH } from "./paths";

const RUNTIME_DIR_PATH = path.join(STATE_DIR_PATH, "runtime");

function ensureRuntimeDir(): void {
    fs.mkdirSync(RUNTIME_DIR_PATH, { recursive: true });
}

function runtimeStatePath(key: string): string {
    if (!/^[a-z0-9-]+$/i.test(key)) {
        throw new Error(
            `[e2e-pw] Invalid runtime state key "${key}". Use only letters, digits, and dashes.`,
        );
    }

    return path.join(RUNTIME_DIR_PATH, `${key}.json`);
}

export function writeRuntimeState(key: string, value: unknown): void {
    ensureRuntimeDir();

    fs.writeFileSync(runtimeStatePath(key), JSON.stringify(value, null, 2));
}

export function readRuntimeState<T>(key: string): T {
    const file = runtimeStatePath(key);

    if (!fs.existsSync(file)) {
        throw new Error(
            `[e2e-pw] Runtime state "${key}" not found at ${file}. ` +
                `Did the producing admin spec run before the consumer?`,
        );
    }

    return JSON.parse(fs.readFileSync(file, "utf-8")) as T;
}
