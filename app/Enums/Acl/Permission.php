<?php

namespace App\Enums\Acl;

enum Permission: string
{
    case MANAGE_SETTINGS = 'manage settings'; // media path, plus edition, SSO, etc. (ADMIN only)
    case MANAGE_ALL_USERS = 'manage all users'; // create, edit, delete any user (ADMIN only)
    case MANAGE_ORG_USERS = 'manage org users'; // create, edit, delete users in organization (MODERATOR+)
    case MANAGE_ARTISTS = 'manage artists'; // manage artists under a manager (MANAGER)
    case UPLOAD_CONTENT = 'upload content'; // upload songs, podcasts, radio stations (ARTIST+)
    case PUBLISH_CONTENT = 'publish content'; // mark content as public (MODERATOR+)
}
