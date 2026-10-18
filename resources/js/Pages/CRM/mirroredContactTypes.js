// Spiegelt CrmSystemContactTypeEnum::mirroredSlugs(): Kontakte dieser Typen werden aus einer anderen Quelle gepflegt.
const mirroredSlugs = ['user', 'freelancer', 'service_provider', 'ticketing']

export const isMirroredContactType = (slug) => mirroredSlugs.includes(slug)
