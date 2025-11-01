# WhatsApp Chat 2.0

WhatsApp Chat 2.0 is an advanced WordPress plugin that adds a highly customizable WhatsApp support widget to your site. Provide round-the-clock availability, route visitors to the right support agent, and control the branding to match your design system.

## Features

- Floating WhatsApp button with custom colors, label, and placement.
- Multi-agent routing with role descriptions, greetings, and drag-free prioritization.
- Business hours scheduler with per-day controls to display offline messaging outside working hours.
- GDPR/consent notice support and customizable offline text.
- Display rules to limit the widget to selected content types or page IDs.

## Installation

1. Download or clone this repository into your WordPress plugin directory: `wp-content/plugins/`.
2. Ensure the folder name remains `whatsapp-chat-2.0`.
3. Activate **WhatsApp Chat 2.0** via the WordPress Plugins screen.

## Configuration

Navigate to **Settings → WhatsApp Chat 2.0** and configure the following sections:

1. **General settings**
   - Enable or disable the widget globally.
   - Set a fallback WhatsApp number and default pre-filled message.
   - Provide welcome and offline messages as well as optional GDPR text.
2. **Floating button**
   - Adjust the label, colors, position, and icon visibility for the floating launcher.
3. **Availability schedule**
   - Choose working days and start/end times. Outside of these hours visitors will see the offline message.
4. **Support agents**
   - Add one or more agents with their phone number, title, custom greeting, and priority for display order.
5. **Display rules**
   - Decide where the widget appears (site-wide, homepage, specific pages/posts, etc.).

Changes are saved using the native WordPress Settings API, so settings are stored safely and can be exported with standard tools.

## Development

The plugin uses a small vanilla JS bundle for both the admin repeater interface and the front-end toggle logic. Styles are written in plain CSS and can be extended or replaced using WordPress hooks or by overriding the enqueued assets.

## License

Licensed under the [GNU General Public License v2 or later](LICENSE).
