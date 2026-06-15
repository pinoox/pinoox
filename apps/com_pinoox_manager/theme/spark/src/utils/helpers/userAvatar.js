import defaultAvatarUrl from '@/assets/media/default-avatar.svg';

export function userDisplayName(user) {
  const profile = user ?? {};
  const fullName = [profile.fname, profile.lname].filter(Boolean).join(' ').trim();

  return fullName || profile.username || profile.email || 'کاربر';
}

export function userAvatarSrc(user) {
  if (user?.isAvatar && user?.avatar_thumb) {
    return user.avatar_thumb;
  }

  return defaultAvatarUrl;
}
