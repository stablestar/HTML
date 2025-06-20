# WP Ticket System

This plugin provides a basic ticket management system integrated with WordPress. Features include:

- Email ingestion via scheduled events (placeholder for IMAP/POP3 integration).
- Custom post type `wpts_ticket` and `wpts_priority` taxonomy.
- Assignment of tickets to users and priority selection through meta boxes.
- REST API endpoint `/wp-json/wpts/v1/tickets` to fetch tickets.
- Cron schedule for checking the shared inbox every five minutes.

This code is a starting point for a finance department ticket workflow and can be extended to integrate with external systems like Slack or CRMs.
