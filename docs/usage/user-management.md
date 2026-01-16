# User Management

Koel supports multiple users with different roles and permissions.

## User Roles

* **Administrator** - Full system access including configuration, user management, and all content operations.
* **Moderator** - Manage users within their organization and publish/unpublish content.
* **Manager** - Manage a group of assigned artists and upload content on their behalf.
* **Artist** - Upload and manage their own content (private by default).
* **User** - Listen and download public content.

:::tip Multi-library support
Each user manages their own library with the ability to share and collaborate with others.
Content is private by default and can only be made public by moderators or administrators.
A private library can only be managed by its owner or assigned managers/moderators.
:::

## First Admin User
Upon installation, Koel prompts to create a first (default) admin user.
If you're using the [Docker image](../guide/getting-started#using-docker), the admin user will be created automatically with these credentials:

```
Email: admin@koel.dev
Password: KoelIsCool
```

<div class="danger custom-block" style="padding-top: 8px">
Make sure to change these credentials immediately after logging in for the first time!
</div>

## Changing First Admin Password
If you forgot the default admin’s password and are unable to log in, you can change it via the command line:

```bash
php artisan koel:admin:change-password
```

## Adding More Users

With the `manage users` permission (admin and manager roles), you can add more users and manage their profiles under
Manage → Users. If Koel has been [configured](../guide/getting-started#configure-a-mailer) with a mailer, you can also invite a user via email.
You cannot add, invite, edit, or delete users whose roles are higher than yours.

## Changing User Roles

User roles can be changed via the web interface from the Users page if the current user has the `manage users`
permission, or via the [command line](../cli-commands#koel-admin-set-user-role).


