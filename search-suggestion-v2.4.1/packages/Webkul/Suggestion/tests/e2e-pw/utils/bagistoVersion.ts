import { env } from "./env";

export interface BagistoVersion {
    major: number;
    minor: number;
    patch: number;
}

export function parseBagistoVersion(raw: string): BagistoVersion {
    const match = raw.trim().match(/^(\d+)\.(\d+)(?:\.(\d+))?/);

    if (!match) {
        throw new Error(
            `[e2e-pw] Invalid BAGISTO_VERSION "${raw}". Expected a semver-like string (e.g. "2.3" or "2.3.17").`,
        );
    }

    return {
        major: Number(match[1]),
        minor: Number(match[2]),
        patch: match[3] ? Number(match[3]) : 0,
    };
}

export function getBagistoVersion(): BagistoVersion {
    return parseBagistoVersion(env.bagistoVersion);
}

export function isBagistoVersionAtLeast(target: string): boolean {
    const current = getBagistoVersion();
    const wanted = parseBagistoVersion(target);

    if (current.major !== wanted.major) {
        return current.major > wanted.major;
    }

    if (current.minor !== wanted.minor) {
        return current.minor > wanted.minor;
    }

    return current.patch >= wanted.patch;
}
