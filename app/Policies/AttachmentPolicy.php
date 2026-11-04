<?php

namespace App\Policies;

use App\Models\Attachment;
use App\Models\Consultation;
use App\Models\User;

class AttachmentPolicy
{
    /**
     * Determine whether the user can view attachments.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('consultations.view');
    }

    /**
     * Determine whether the user can view an attachment.
     */
    public function view(User $user, Attachment $attachment): bool
    {
        return $user->can('consultations.view');
    }

    /**
     * Laboratory results may arrive after a consultation is finalized.
     */
    public function create(User $user): bool
    {
        return $user->can('consultations.update');
    }

    /**
     * Attachment metadata is immutable once uploaded.
     */
    public function update(User $user, Attachment $attachment): bool
    {
        return false;
    }

    /**
     * Attachments cannot be deleted from a finalized consultation.
     */
    public function delete(User $user, Attachment $attachment): bool
    {
        return $user->can('consultations.update')
            && (! $attachment->attachable instanceof Consultation || $attachment->attachable->isDraft());
    }

    /**
     * Determine whether the user can restore an attachment.
     */
    public function restore(User $user, Attachment $attachment): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete an attachment.
     */
    public function forceDelete(User $user, Attachment $attachment): bool
    {
        return false;
    }
}
