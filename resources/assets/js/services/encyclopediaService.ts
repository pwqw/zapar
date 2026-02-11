import { cache } from '@/services/cache'
import { http } from '@/services/http'
import { albumStore } from '@/stores/albumStore'
import { artistStore } from '@/stores/artistStore'
import { playableStore } from '@/stores/playableStore'

export const encyclopediaService = {
  async fetchForArtist (artist: Artist) {
    artist = artistStore.syncWithVault(artist)[0]
    const cacheKey = ['artist.info', artist.id]
    if (cache.has(cacheKey)) {
      return cache.get<ArtistInfo>(cacheKey)
    }

    const info = await http.get<ArtistInfo | null>(`artists/${artist.id}/information`)

    info && cache.set(cacheKey, info)
    // Only set image from encyclopedia when the artist has no image, so we never overwrite a user-set image.
    if (info?.image && !artist.image) {
      artist.image = info.image
    }

    return info
  },

  async fetchForAlbum (album: Album) {
    album = albumStore.syncWithVault(album)[0]
    const cacheKey = ['album.info', album.id, album.name]

    if (cache.has(cacheKey)) {
      return cache.get<AlbumInfo>(cacheKey)
    }

    const info = await http.get<AlbumInfo | null>(`albums/${album.id}/information`)
    info && cache.set(cacheKey, info)

    if (info?.cover) {
      album.cover = info.cover
      playableStore.byAlbum(album).forEach(song => (song.album_cover = info.cover!))
    }

    return info
  },

  async clearArtistEncyclopediaData (artist: Artist) {
    artist = artistStore.syncWithVault(artist)[0]
    await http.delete(`artists/${artist.id}/information`)
    cache.remove(['artist.info', artist.id])
    artist.image = ''
    artistStore.syncWithVault(artist)
  },

  async clearAlbumEncyclopediaData (album: Album) {
    album = albumStore.syncWithVault(album)[0]
    await http.delete(`albums/${album.id}/information`)
    cache.remove(['album.info', album.id, album.name])
    album.cover = ''
    albumStore.syncWithVault(album)
    playableStore.byAlbum(album).forEach(song => (song.album_cover = ''))
  },
}
