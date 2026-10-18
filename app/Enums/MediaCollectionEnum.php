<?php

namespace App\Enums;

/**
 * Media-Collections – der Wert ist zugleich der Unterordner auf der Disk (siehe CollectionPathGenerator).
 */
enum MediaCollectionEnum: string
{
    // Website (Disk "uploads")
    case Artists = 'artists';
    case Gallery = 'gallery';
    case News = 'news';
    case Team = 'team';
    case Shows = 'shows';

    // Band-Manager (Disk "band-manager")
    case BandPhotos = 'photos';
    case BandLogos = 'logos';
    case MemberPhotos = 'member-photos';
    case Documents = 'documents';
    case StagePlots = 'stage-plots';
}
