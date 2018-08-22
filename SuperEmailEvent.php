<?php
namespace Super\SuperEmailBundle;

final class SuperEmailEvent
{
    /**
     * @var string
     */
    const BEFORE_RENDER_HTML = 'super_email.before_render_html';

    /**
     * @var string
     */
    const AFTER_RENDER_HTML = 'super_email.after_render_html';

    /**
     * @var string
     */
    const BEFORE_SEND = 'super_email.before_send';

    /**
     * @var string
     */
    const AFTER_SEND = 'super_email.after_send';
}