<script setup lang="ts">
import { motion } from 'motion-v';
import { MemberApplicationRequests } from '@/services/requests/MemberApplicationRequests';
import { sectionRise } from '@/components/Home/anims';
import type {
  ApplicationAnswers,
  ApplicationField,
  ApplicationFormSchema,
} from '@/types/applicationForm';

/*
  The one page on the site that is a TOOL rather than a document, so the
  craft shifts from typography to input design.

  It never hard-codes a field: every question comes from
  GET /api/application-form, and each field's `type` picks the control.
  Adding a question is an admin-panel edit, not a change here.

  Zero PrimeVue — the controls are the local Form/ components. Aura's
  token cascade would have to be fought at every step to reach this
  design, and the whole theme engine was the largest dependency left in
  the bundle after Three.js.
*/
/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
/*---------------------------------------------
/  VARIABLES
---------------------------------------------*/
const { t } = useI18n();
const { locale } = useLocale();
const toasts = useToasts();

/*
  Keyed on the locale so switching language re-asks for the translated
  labels — see syncValues for why that does not cost the applicant their
  answers.
*/
const { data: schema, status } = useQuery<ApplicationFormSchema>({
  key: ['application-form', locale],
  request: () => MemberApplicationRequests.form(locale.value as string),
  cache: true,
  staleTime: 60,
  refetchTime: 600,
});

const answers = ref<ApplicationAnswers>({});
const submitState = ref<'idle' | 'submitting' | 'success'>('idle');

/** Per-field messages from the API's 422, keyed by field key. */
const fieldErrors = ref<Record<string, string>>({});
/*---------------------------------------------
/  METHODS
---------------------------------------------*/
/**
 * Give every question a starting value without discarding anything
 * already typed.
 *
 * This MERGES rather than replaces, and that is the whole point: the
 * schema arrives again on a locale switch, and losing a half-filled form
 * to a language change would be its own bug report.
 */
const syncValues = (next?: ApplicationFormSchema | null) => {
  if (!next) {
    return;
  }
  for (const section of next.sections) {
    for (const field of section.fields) {
      if (answers.value[field.key] === undefined) {
        answers.value[field.key] = field.type === 'checkbox' ? [] : '';
      }
    }
  }
};

const isAnswered = (field: ApplicationField) => {
  const value = answers.value[field.key];

  return Array.isArray(value) ? value.length > 0 : !!value;
};

const submit = async () => {
  submitState.value = 'submitting';
  fieldErrors.value = {};

  /*
    Send only what was answered. The API validates against the same
    questions, and an empty string for an optional question would be
    stored as an answer nobody gave.
  */
  const payload: ApplicationAnswers = {};
  for (const [key, value] of Object.entries(answers.value)) {
    if (value === undefined || value === '' || (Array.isArray(value) && !value.length)) {
      continue;
    }
    payload[key] = value;
  }

  try {
    await MemberApplicationRequests.submit(payload);
    submitState.value = 'success';
    window.scrollTo({ top: 0, behavior: 'smooth' });
  } catch (error) {
    submitState.value = 'idle';

    /*
      A 429 is recoverable without retyping — the form stays filled and
      says to wait. That is the reason this is an in-page state rather
      than a redirect: the throttle is 10/minute and a redirect would
      throw the answers away.
    */
    const responseStatus = (error as { status?: number })?.status;
    const body = (error as { data?: { errors?: Record<string, string[]> } })?.data;

    if (responseStatus === 429) {
      toasts.error(t('form.throttled'));

      return;
    }

    if (body?.errors) {
      fieldErrors.value = Object.fromEntries(
        Object.entries(body.errors).map(([key, messages]) => [key, messages[0] ?? '']),
      );
      toasts.error(t('form.errorBody'));

      return;
    }

    toasts.error(t('form.errorTitle'));
  }
};

const startOver = () => {
  answers.value = {};
  fieldErrors.value = {};
  syncValues(schema.value);
  submitState.value = 'idle';
};
/*---------------------------------------------
/  COMPUTED
---------------------------------------------*/
const sections = computed(() => schema.value?.sections ?? []);
const submitting = computed(() => submitState.value === 'submitting');

/** A section counts as done once its required questions are answered. */
const sectionsDone = computed(() => sections.value.filter(
  (section) => section.fields.filter((field) => field.required).every(isAnswered),
).length);
/*---------------------------------------------
/  WATCHERS
---------------------------------------------*/
watch(schema, syncValues, { immediate: true });
/*---------------------------------------------
/  HOOKS
---------------------------------------------*/
</script>

<template>
  <div class="join-page">
    <HtmlTitle :title="t('join.title')" />

    <SectionShell
      class="join-section"
      :eyebrow="t('join.eyebrow')"
      :title="t('join.title')"
    >
      <!--
        The same sky the hero and the rocket sheet are set against. This
        page asks someone to join a rocketry team; sending them to a bare
        form on flat ink is the one screen where the site stops looking
        like itself. Full-bleed, so it goes in the bleed slot — the
        default slot is inside the reading measure.
      -->
      <template #bleed>
        <Starfield class="join-section__stars" :opacity="0.5" />
        <div class="join-section__glow" aria-hidden="true" />
      </template>

      <!-- The success state REPLACES the form rather than redirecting, so a
           reader can see what happened without losing the page. -->
      <motion.div v-if="submitState === 'success'" v-bind="sectionRise()" class="done">
        <h3>{{ t('form.successTitle') }}</h3>
        <p class="lede">{{ t('form.successBody') }}</p>
        <div class="cta-row">
          <RouterLink to="/" class="btn">{{ t('notFound.home') }}</RouterLink>
          <button type="button" class="btn btn--ghost" @click="startOver">
            {{ t('form.again') }}
          </button>
        </div>
      </motion.div>

      <template v-else>
        <FetchError v-if="status === 'FAILED' && !sections.length" />
        <Skeleton v-else-if="status === 'PENDING' && !sections.length" :rows="4" height="90px" />

        <form v-else class="join-form" novalidate @submit.prevent="submit">
          <p class="lede join-form__lede">{{ t('join.lede') }}</p>

          <FormProgress :total="sections.length" :done="sectionsDone" />

          <section v-for="section in sections" :key="section.key" class="join-form__section">
            <h3>{{ section.title }}</h3>
            <p v-if="section.description" class="join-form__hint">{{ section.description }}</p>

            <FormField
              v-for="field in section.fields"
              :key="field.key"
              :id="`f-${field.key}`"
              :label="field.label"
              :help="field.help"
              :error="fieldErrors[field.key] ?? null"
              :required="field.required"
            >
              <template #default="{ id, describedBy, invalid }">
                <TextArea
                  v-if="field.type === 'textarea'"
                  :id="id"
                  v-model="answers[field.key] as string"
                  :placeholder="field.placeholder ?? ''"
                  :maxlength="field.maxLength || undefined"
                  :described-by="describedBy"
                  :invalid="invalid"
                  :required="field.required"
                />
                <SelectInput
                  v-else-if="field.type === 'select'"
                  :id="id"
                  v-model="answers[field.key] as string"
                  :options="field.options"
                  :placeholder="field.placeholder || t('form.choose')"
                  :described-by="describedBy"
                  :invalid="invalid"
                  :required="field.required"
                />
                <OptionPills
                  v-else-if="field.type === 'radio' || field.type === 'checkbox'"
                  :id="id"
                  v-model="answers[field.key]"
                  :options="field.options"
                  :multiple="field.type === 'checkbox'"
                  :described-by="describedBy"
                  :invalid="invalid"
                />
                <TextInput
                  v-else
                  :id="id"
                  v-model="answers[field.key] as string"
                  :type="field.type === 'email' ? 'email' : 'text'"
                  :placeholder="field.placeholder ?? ''"
                  :maxlength="field.maxLength || undefined"
                  :described-by="describedBy"
                  :invalid="invalid"
                  :required="field.required"
                />
              </template>
            </FormField>
          </section>

          <button type="submit" class="btn join-form__submit" :disabled="submitting">
            {{ submitting ? t('form.sending') : t('form.submit') }}
          </button>
        </form>
      </template>
    </SectionShell>
  </div>
</template>

<style lang="scss" scoped>
/*
  The section head and the form share one centred column.

  The head is a flex row that spreads to the container's full width, so
  centring the form alone would have left the heading hard against the
  left margin and the form in the middle — two different axes on one
  screen. Both are constrained to the same measure instead, and the head
  centres its own contents rather than pushing them apart.
*/
.join-section {
  overflow: hidden;

  :deep(.section__head),
  :deep(.section__rule),
  :deep(.join-form),
  :deep(.done) {
    max-width: 680px;
    margin-inline: auto;
  }

  :deep(.section__head) {
    flex-direction: column;
    align-items: center;
    text-align: center;
  }
}

.join-section__stars {
  // Oversized so nothing can pull the field's edge into frame.
  inset: -6% -2% !important;
}

.join-section__glow {
  position: absolute;
  inset: 0;
  background: radial-gradient(70% 46% at 50% 0%, rgb(241 171 60 / 8%), transparent 64%);
  pointer-events: none;
}

.join-form {
  max-width: 680px;
  margin-top: clamp(32px, 4vw, 56px);
  margin-inline: auto;
}

.join-form__lede {
  margin-bottom: clamp(30px, 4vw, 48px);
}

.join-form__section {
  margin-bottom: clamp(40px, 5vw, 64px);

  h3 {
    margin-bottom: 8px;
    font-size: var(--fs-h3);
  }
}

.join-form__hint {
  margin-bottom: 26px;
  color: var(--text-mid);
  font-size: 15px;
}

.join-form__submit {
  justify-content: center;
  width: 100%;

  &:disabled {
    opacity: 0.6;
    cursor: progress;
  }
}

.done {
  max-width: 52ch;
  margin-top: clamp(32px, 4vw, 56px);
  margin-inline: auto;
  text-align: center;

  h3 {
    font-size: var(--fs-h2);
  }

  .lede {
    margin-top: 16px;
  }
}

.cta-row {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  justify-content: center;
  margin-top: 30px;
}
</style>
