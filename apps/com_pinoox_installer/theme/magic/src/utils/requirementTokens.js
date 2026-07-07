const DEFAULT_PHP_REQUIREMENTS = {
    minimum: '8.2.0',
    constraint: '^8.2',
    php_short: '8.2',
}

export function normalizePhpRequirements(source) {
    const php = source?.php ?? source?.requirements?.php ?? source

    if (!php || typeof php !== 'object') {
        return {...DEFAULT_PHP_REQUIREMENTS}
    }

    const minimum = String(php.minimum ?? php.required ?? DEFAULT_PHP_REQUIREMENTS.minimum)
    const constraint = String(php.constraint ?? php.composer_constraint ?? DEFAULT_PHP_REQUIREMENTS.constraint)
    const phpShort = String(php.php_short ?? shortPhpVersion(minimum))

    return {
        minimum,
        constraint,
        php_short: phpShort,
    }
}

export function shortPhpVersion(minimum) {
    const match = String(minimum).match(/^(\d+\.\d+)/)

    return match ? match[1] : String(minimum)
}

export function requirementTokens(requirements) {
    const php = normalizePhpRequirements(requirements)

    return {
        required: php.minimum,
        constraint: php.constraint,
        php_short: php.php_short,
    }
}

export function replaceRequirementTokens(text, requirements) {
    if (typeof text !== 'string' || text === '') {
        return text ?? ''
    }

    const tokens = requirementTokens(requirements)

    return Object.entries(tokens).reduce(
        (result, [key, value]) => result.replaceAll(`{${key}}`, value),
        text,
    )
}
