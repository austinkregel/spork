<script setup>
import {computed, onMounted, ref} from "vue";
import {router, usePage} from "@inertiajs/vue3";
import debounce from "lodash/debounce"

const user = usePage()?.props?.auth?.user;

const data = ref([]);
const jobs = computed(() => {
  return data.value.reduce((jobs_, job) => {
    updateForBatches(job.batch)
    return {
      ...jobs_,
      [job.batch.id]: {
        ...(jobs_[job.batch.id] ?? {}),
        ...job.batch,
      }
    };
  }, {})
});

const progress = computed(() => {
  return Object.values(jobs.value).reduce((progress_, job) => {
    return {
      ...progress_,
      [job.id]:  Math.round((job.total_jobs - job.pending_jobs) / job.total_jobs * 100)
    };
  }, {})
});
const updateForBatches = (job) => {
  // job = {
  //     "id": "9ee002ea-167e-404e-9a34-d58c99d2942a",
  //     "name": "Updatch Resources From Credentials",
  //     "total_jobs": 9,
  //     "pending_jobs": 8,
  //     "failed_jobs": 0,
  //     "failed_job_ids": [],
  //     "options": "a:4:{s:13:\"allowFailures\";b:1;s:4:\"then\";a:1:{i:0;O:47:\"Laravel\\SerializableClosure\\SerializableClosure\":1:{s:12:\"serializable\";O:46:\"Laravel\\SerializableClosure\\Serializers\\Signed\":2:{s:12:\"serializable\";s:403:\"O:46:\"Laravel\\SerializableClosure\\Serializers\\Native\":5:{s:3:\"use\";a:0:{}s:8:\"function\";s:183:\"function (\\Illuminate\\Bus\\Batch $batch) {\n                broadcast(new \\App\\Events\\Models\\JobBatch\\JobBatchUpdated(\\App\\Models\\JobBatch::firstWhere('id', $batch->id)));\n            }\";s:5:\"scope\";s:38:\"App\\Jobs\\FetchResourcesFromCredentials\";s:4:\"this\";N;s:4:\"self\";s:32:\"00000000000027920000000000000000\";}\";s:4:\"hash\";s:44:\"WHu800rym2ohjzUkOpR0ZfKCgZTlrMt3nLSjPo3nyg4=\";}}}s:5:\"catch\";a:1:{i:0;O:47:\"Laravel\\SerializableClosure\\SerializableClosure\":1:{s:12:\"serializable\";O:46:\"Laravel\\SerializableClosure\\Serializers\\Signed\":2:{s:12:\"serializable\";s:418:\"O:46:\"Laravel\\SerializableClosure\\Serializers\\Native\":5:{s:3:\"use\";a:0:{}s:8:\"function\";s:198:\"function (\\Illuminate\\Bus\\Batch $batch, \\Throwable $e) {\n                broadcast(new \\App\\Events\\Models\\JobBatch\\JobBatchUpdated(\\App\\Models\\JobBatch::firstWhere('id', $batch->id)));\n            }\";s:5:\"scope\";s:38:\"App\\Jobs\\FetchResourcesFromCredentials\";s:4:\"this\";N;s:4:\"self\";s:32:\"000000000000279e0000000000000000\";}\";s:4:\"hash\";s:44:\"imF2WuVZaI0igWz9GFvyZ/gyhI9Y4wz6wFpJ5JquRgk=\";}}}s:7:\"finally\";a:1:{i:0;O:47:\"Laravel\\SerializableClosure\\SerializableClosure\":1:{s:12:\"serializable\";O:46:\"Laravel\\SerializableClosure\\Serializers\\Signed\":2:{s:12:\"serializable\";s:403:\"O:46:\"Laravel\\SerializableClosure\\Serializers\\Native\":5:{s:3:\"use\";a:0:{}s:8:\"function\";s:183:\"function (\\Illuminate\\Bus\\Batch $batch) {\n                broadcast(new \\App\\Events\\Models\\JobBatch\\JobBatchUpdated(\\App\\Models\\JobBatch::firstWhere('id', $batch->id)));\n            }\";s:5:\"scope\";s:38:\"App\\Jobs\\FetchResourcesFromCredentials\";s:4:\"this\";N;s:4:\"self\";s:32:\"00000000000027ac0000000000000000\";}\";s:4:\"hash\";s:44:\"WSvfsjE68Lb3wGeFlGuqm1Sex+QPXVEJm1TUZM5oaI0=\";}}}}",
  //     "cancelled_at": null,
  //     "created_at": 1746849587,
  //     "finished_at": null
  // }
  const noMoreJobs =  job.pending_jobs;

  if (noMoreJobs <= 0) {
    setTimeout(() => {
      if (intervalQuery) {
        clearInterval(intervalQuery);
        intervalQuery = null;
      }

      data.value = data.value.filter((j) => j.batch.id !== job.id);
    }, 5000);
  }
};


let intervalQuery = null;

const setupInterval = () => {
  if (intervalQuery) {
    return;
  }

  intervalQuery = setInterval(() => router.reload({
    only: ['job_batches', 'news']
  }), 5000)
}

onMounted(() => {
  if (!user) {
    return;
  }
  console.log('Subscribing to user channel', `App.Models.User.${user.id}`);
  Echo.private(`App.Models.User.${user.id}`)
      .listen('Models\\JobBatch\\JobBatchCreated', e => {
        data.value.push(e);
        setupInterval();
      })
      .listen('Models\\JobBatch\\JobBatchUpdated', e => {
        data.value.push(e);
        setupInterval();
      })
      .listen('Models\\JobBatch\\JobBatchUpdating', e => {
        data.value.push(e);
        setupInterval();
      })
})
</script>

<template>
  <div class="flex flex-col w-full items-end justify-center h-full text-gray-400">
    <div v-for="job in jobs">
      {{ progress[job.id] }}% {{ job.name }}
    </div>
  </div>
</template>
