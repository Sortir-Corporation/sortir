<?php

namespace App\Enum;

enum EventStatus: string
{
    case CREATED = 'Créée';
    case OPEN = 'Ouverte';
    case CLOSED = 'Clôturée';
    case IN_PROGRESS = 'Activité en cours';
    case PAST = 'passée';
    case CANCELED = 'Annulée';
}
