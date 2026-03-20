# Architecture: laminas-validator

## Purpose
A comprehensive PHP validation library with 60+ validators covering email addresses, hostnames, IP addresses, credit cards, barcodes, dates, file uploads, IBAN, ISBN, GPS coordinates, and more.

## Directory Structure
```
src/
  Validator_Interface.php        # isValid($value): bool + getMessages(): array
  Abstract_Validator.php         # Shared error message management + translation support
  Validator_Plugin_Manager.php   # ServiceManager plugin manager for lazy instantiation
  # Core validators:
  Email_Address.php / Hostname.php / Ip.php / Url.php
  Not_Empty.php / Identical.php / In_Array.php / Regex.php
  String_Length.php / Digits.php / Hex.php / Bitwise.php
  Date.php / Date_Comparison.php / Date_Step.php / Timezone.php
  Number_Comparison.php / Step.php / Callback.php / Conditional.php
  Credit_Card.php / Iban.php / Isbn.php
  Business_Identifier_Code.php / Gps_Point.php / Is_Json_String.php
  # Barcode validators:
  Barcode.php / Barcode/Ean13.php / Barcode/Code128.php / ... (20+ formats)
  # File upload validators:
  File/Size.php / File/Mime_Type.php / File/Extension.php
  File/Image_Size.php / File/Upload_File.php / File/Hash.php / ...
  # Sitemap validators:
  Sitemap/Loc.php / Sitemap/Changefreq.php / Sitemap/Priority.php
  # Hostname sub-validators:
  Hostname/Biz.php / Hostname/Com.php / Hostname/Cn.php / Hostname/Jp.php
  Explode.php                    # Runs a validator against each element of a delimited string
  Validator_Chain.php            # Chains multiple validators with AND/OR semantics
  Translator/Translator_Aware_Interface.php  # i18n error message support
  Exception/                     # Typed exceptions
  Config_Provider.php / Module.php
```

## Key Design Decisions
- **Single `isValid()` method** — all validators implement the same two-method interface, making them trivially composable in chains.
- **Error message templates** — messages are defined as class constants and filled via `sprintf()` substitution. All messages are translatable via `laminas-i18n`.
- **Plugin manager** — short-name aliases (e.g., `'EmailAddress'`, `'StringLength'`) enable configuration-driven validator instantiation without FQCN knowledge.
- **`Validator_Chain`** — validators are composed in a chain; each validator runs in order, collecting all errors or short-circuiting on first failure depending on the `setBreakChainOnFailure()` flag.
- **Conditional validator** — `Conditional` wraps another validator and only runs it if a condition is met, enabling complex field-dependency rules.

## Extension Points
- Implement `Validator_Interface` to add a custom validator.
- Implement `Translator_Aware_Interface` to get automatic i18n for error messages.
- Register a custom validator alias in `Validator_Plugin_Manager`.

## Dependency Flow
```
Validator_Chain::isValid($value)
  └─ [for each validator in chain]
       └─ ValidatorN::isValid($value) → bool
            └─ getMessages() → ['key' => 'human-readable message']
  └─ aggregate all messages or break on first failure
```
