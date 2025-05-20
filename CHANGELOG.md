# Changelog

All notable changes to this project will be documented in this file.

---

## [3.0] - 2025-05-20 
### Added  
- Editable ACF field values directly from the plugin interface  
- Sync mechanism to update ACF fields on the selected page  
- Admin notice prompting users to clear their cache manually after saving  
- Hyperlink to ACF field group settings when no custom fields are found  

### Changed  
- UI refinements and help text updates for clarity  
- Improved shortcode copy button reliability  
- Enhanced shortcode handling to avoid wrapping values in unnecessary characters  

### Fixed  
- Bug where changes made to values in the plugin did not reflect on the front-end  
- Shortcode names not persisting on save  
- Shortcode fallback logic now consistently returns a clean message when fields are undefined  

---

## [2.0] - 2025-05-20  
### Added  
- Admin UI for selecting ACF source page  
- Auto-detect ACF fields from selected page  
- Input field to define custom shortcode names per ACF field  
- Copy-to-clipboard button for shortcodes  

### Changed  
- Refactored code to support dynamic shortcode registration  
- Improved fallback message if value is missing  

---

## [1.0] - 2025-05-20  
### Added  
- Initial plugin release with hardcoded shortcodes and ACF option page usage  