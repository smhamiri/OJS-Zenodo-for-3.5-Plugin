# Zenodo Plugin for OJS 3.5.0-5

An OJS generic plugin for exporting journal publications to Zenodo using the Invenio RDM API.

## Compatibility
- Target: OJS 3.5.0-5
- Requires the PHP version supported by your OJS 3.5.0-5 installation.
- This package has been syntax-checked with the PHP CLI available in the build environment. A full runtime integration test requires an installed OJS 3.5.0-5 instance.

## Installation
1. Back up your OJS database and files.
2. Extract this archive. The resulting directory must be named `zenodo`.
3. Upload the `zenodo` directory to `plugins/generic/zenodo/` in your OJS installation.
4. Ensure the web server user can read the plugin files.
5. In OJS, open **Settings → Website → Plugins** (or the plugin management page in your installation), locate **Zenodo Export Plugin**, and enable it.
6. Open **Tools → Zenodo Export Plugin** and configure the API key and other settings.

If the plugin does not appear, check the PHP/OJS error log and confirm the directory is exactly `plugins/generic/zenodo/`.

## Zenodo setup
- For testing, create a Zenodo Sandbox account and use its API key. Enable **Test mode** in the plugin settings.
- For production, disable **Test mode** and use a production Zenodo API key.
- The plugin expects a DOI unless the option to let Zenodo mint a DOI is enabled. A DOI minted by Zenodo is not written back to OJS.
- Automatic publishing is optional. Published Zenodo records may not be easily deleted, so review metadata before enabling it.

## API
This plugin uses the Invenio RDM API, not Zenodo's legacy API.

## License
GNU General Public License v3. See `LICENSE`.


