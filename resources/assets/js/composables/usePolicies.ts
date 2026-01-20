import { computed } from 'vue'
import { arrayify } from '@/utils/helpers'
import { useAuthorization } from '@/composables/useAuthorization'
import { useKoelPlus } from '@/composables/useKoelPlus'
import { acl } from '@/services/acl'
import { commonStore } from '@/stores/commonStore'
import { authService } from '@/services/authService'

export const usePolicies = () => {
  const { currentUser } = useAuthorization()
  const { isPlus } = useKoelPlus()

  const allowDownload = computed(() => commonStore.state.allows_download && !authService.isAnonymous())

  const currentUserCan = {
    editSong: (songs: MaybeArray<Song>) => {
      if (currentUser.value.permissions.includes('upload content')) {
        return true
      }

      if (!isPlus.value) {
        return false
      }

      return arrayify(songs).every(song => song.owner_id === currentUser.value.id)
    },

    editPlaylist: (playlist: Playlist) => playlist.owner_id === currentUser.value.id,
    editAlbum: async (album: Album) => await acl.checkResourcePermission('album', album.id, 'edit'),
    editArtist: async (artist: Artist) => await acl.checkResourcePermission('artist', artist.id, 'edit'),
    editUser: async (user: User) => await acl.checkResourcePermission('user', user.id, 'edit'),
    deleteUser: async (user: User) => await acl.checkResourcePermission('user', user.id, 'delete'),

    editRadioStation: async (station: RadioStation) => {
      return await acl.checkResourcePermission('radio-station', station.id, 'edit')
    },

    deleteRadioStation: async (station: RadioStation) => {
      return await acl.checkResourcePermission('radio-station', station.id, 'delete')
    },

    // If the user has the permission, they can always add a radio station, even in demo mode.
    addRadioStation: () => !window.IS_DEMO || currentUser.value.permissions.includes('upload content'),

    manageSettings: () => currentUser.value.permissions.includes('manage settings'),
    manageUsers: () => currentUser.value.permissions.includes('manage all users') || currentUser.value.permissions.includes('manage org users') || currentUser.value.permissions.includes('manage artists'),
    uploadSongs: () => isPlus.value || currentUser.value.permissions.includes('upload content'),

    // New methods for the refactored role system
    canPublish: () => {
      // Only moderators and admins can publish (make items public)
      return currentUser.value.permissions.includes('publish content')
    },

    canUploadAs: (artistId: string) => {
      // Managers can upload as their managed artists
      if (!currentUser.value.permissions.includes('manage artists')) {
        return false
      }

      // Current user can always upload as themselves
      if (artistId === currentUser.value.id) {
        return true
      }

      // Admins can upload as anyone
      return currentUser.value.permissions.includes('manage settings')
    },

    canManageArtists: () => {
      // Only managers (and admins via inheritance) can manage artists
      return currentUser.value.permissions.includes('manage artists')
    },
  }

  return {
    currentUserCan,
    allowDownload,
  }
}
