export class SwipeTrailerCandidates {
    constructor(loadDetails, concurrency = 3) {
        this.loadDetails = loadDetails;
        this.concurrency = concurrency;
        this.checked = new Map();
    }

    async check(movies, onConfirmed) {
        const candidates = [...new Map(movies.map(movie => [movie.id, movie])).values()];
        const failed = [];
        let accepted = 0;
        let cursor = 0;
        const worker = async () => {
            while (cursor < candidates.length) {
                const movie = candidates[cursor++];
                let details;
                try {
                    details = this.checked.has(movie.id)
                        ? this.checked.get(movie.id)
                        : await this.loadDetails(movie);
                    this.checked.set(movie.id, details);
                } catch {
                    failed.push(movie);
                    continue;
                }
                if (details?.trailer) {
                    onConfirmed(movie, details);
                    accepted++;
                }
            }
        };
        await Promise.all(Array.from({ length: Math.min(this.concurrency, candidates.length) }, worker));
        return { accepted, failed };
    }
}
