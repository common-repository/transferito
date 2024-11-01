<?php echo loadTemplate( 'parts/notice', [
    'title'             => 'Migration Completed',
    'image'             => 'completed-migration',
    'type'              => 'success',
    'externalLink'      => [
        'anchorText'    => 'View Migrated Site',
        'linkURL'       => $data['url']
    ],
    'message'           => "We're happy to let you know that your migration to <a target='_blank' href=" . $data['url'] . ">" . $data['url'] . "</a> has completed",
    'additionalInfo'    => 'You may need to clear your cache to view your migrated site properly.',
    'extraInfo'         => [
        'title'     => 'Liked Using our Plugin?',
        'content'   => 'If you did, please <a target="_blank" class="transferito-log-event" data-event-name="leaveReview" href="https://wordpress.org/support/plugin/transferito/reviews/">click here</a> to leave us a review. Your feedback is very important to us & helps us to continually improve our plugin.'
    ]
]); ?>

